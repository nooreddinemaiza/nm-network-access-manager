#!/bin/bash

set -u

################################################################################
# Configuration
################################################################################

SOURCE_LOG="/var/log/pfsense-dns.log"

DEST_DIR="/var/www/radius/Storage/Logs"
ARCHIVE_DIR="/var/www/radius/Storage/archives"
SECRET_DIR="/var/www/radius/Storage/Files"

DEST_FILE="$DEST_DIR/pfsense-dns.log"
LOG_FILE="$DEST_DIR/dns-sync.log"
STATS_FILE="$DEST_DIR/dns-stats.json"
ERROR_LOG_FILE="$DEST_DIR/dns-errors.json"

# Fichier token accessible par www-data (format PHP)
SECRET_FILE="$SECRET_DIR/cron_secret.php"

API_URL="http://192.168.0.20/cron/update-log"

# Permissions
OWNER="root:www-data"
PERMS="640"          # rw-r----- (root écrit, www-data lit)
DIR_PERMS="750"      # rwxr-x---
SECRET_PERMS="640"   # www-data peut lire

# ============================================================================
# NOUVEAUX PARAMÈTRES DE RETRY INTELLIGENTS
# ============================================================================

# Timeout initial LONG pour laisser le serveur traiter les 30k+ lignes
INITIAL_TIMEOUT=900          # 15 minutes (900 secondes) pour le premier envoi
CONNECT_TIMEOUT=10           # 10 secondes pour établir la connexion

# Délai avant de vérifier si le traitement est terminé (si timeout)
VERIFICATION_DELAY=120       # 2 minutes d'attente après un timeout

# Timeout pour les vérifications suivantes (plus court, juste pour check status)
CHECK_TIMEOUT=30             # 30 secondes pour les checks de statut

# Nombre maximum de cycles complets (envoi + vérifications)
MAX_CYCLES=3                 # 3 cycles maximum

# Délai entre les cycles si still processing
RETRY_DELAY=600              # 10 minutes entre chaque nouveau cycle

# Binaires
OPENSSL_BIN="/usr/bin/openssl"
CURL_BIN="/usr/bin/curl"
DATE_BIN="/bin/date"
AWK_BIN="/usr/bin/awk"
WC_BIN="/usr/bin/wc"
STAT_BIN="/usr/bin/stat"
MKDIR_BIN="/bin/mkdir"
CHOWN_BIN="/bin/chown"
CHMOD_BIN="/bin/chmod"
CAT_BIN="/bin/cat"
ECHO_BIN="/bin/echo"
JQ_BIN="/usr/bin/jq"

################################################################################
# Date
################################################################################

TODAY="$($DATE_BIN +"%Y-%m-%d")"
NOW_TIMESTAMP="$($DATE_BIN '+%Y-%m-%d %H:%M:%S')"
NOW_ISO="$($DATE_BIN -Iseconds)"

################################################################################
# Logging
################################################################################

log() {
    local message="$1"
    local level="${2:-INFO}"
    local timestamp="$($DATE_BIN '+%Y-%m-%d %H:%M:%S')"
    
    if [ -w "$LOG_FILE" ] 2>/dev/null; then
        $ECHO_BIN "[$timestamp] [$level] $message" >> "$LOG_FILE"
    else
        $ECHO_BIN "[$timestamp] [$level] $message" >&2
    fi
}

################################################################################
# Gestion du fichier de log quotidien
################################################################################

manage_daily_log() {
    local log_date=""
    local execution_count=0
    local first_start=""
    local total_lines=0
    local success_marker="$DEST_DIR/.last_sync_success"
    
    # Si le fichier existe, vérifier s'il est du jour
    if [ -f "$LOG_FILE" ]; then
        # Extraire la première date du fichier
        log_date=$($AWK_BIN -F'[][]' 'NR==1 {print substr($2, 1, 10)}' "$LOG_FILE" 2>/dev/null || echo "")
        
        # Si ce n'est pas la date d'aujourd'hui, archiver et réinitialiser
        if [ "$log_date" != "$TODAY" ] && [ -n "$log_date" ]; then
            # Vérifier si la dernière synchronisation a réussi
            if [ -f "$success_marker" ]; then
                # Archiver seulement si la dernière sync a réussi
                local archive_file="$ARCHIVE_DIR/dns-sync-$log_date.log"
                if mv "$LOG_FILE" "$archive_file" 2>/dev/null || sudo mv "$LOG_FILE" "$archive_file" 2>/dev/null; then
                    $ECHO_BIN "Log précédent archivé: $archive_file" >&2
                    # Appliquer les permissions sur l'archive
                    $CHOWN_BIN "$OWNER" "$archive_file" 2>/dev/null || \
                        sudo $CHOWN_BIN "$OWNER" "$archive_file" 2>/dev/null || true
                    $CHMOD_BIN "$PERMS" "$archive_file" 2>/dev/null || \
                        sudo $CHMOD_BIN "$PERMS" "$archive_file" 2>/dev/null || true
                else
                    $ECHO_BIN "ATTENTION: Impossible d'archiver le log précédent" >&2
                fi
                # Supprimer le marqueur de succès
                rm -f "$success_marker" 2>/dev/null || sudo rm -f "$success_marker" 2>/dev/null || true
            else
                # Pas de sync réussie, supprimer le log sans archiver
                $ECHO_BIN "ATTENTION: Log précédent supprimé (dernière sync échouée, pas d'archivage)" >&2
                rm -f "$LOG_FILE" 2>/dev/null || sudo rm -f "$LOG_FILE" 2>/dev/null || true
            fi
        elif [ "$log_date" = "$TODAY" ]; then
            # Lire les stats existantes
            execution_count=$($AWK_BIN '/Exécution #/ {count++} END {print count}' "$LOG_FILE" 2>/dev/null || echo 0)
            first_start=$($AWK_BIN -F'[][]' 'NR==1 {print $2}' "$LOG_FILE" 2>/dev/null || echo "$NOW_TIMESTAMP")
            total_lines=$($AWK_BIN -F': ' '/Total lignes extraites/ {sum+=$2} END {print sum+0}' "$LOG_FILE" 2>/dev/null || echo 0)
        fi
    fi
    
    # Si nouveau jour ou fichier n'existe pas
    if [ ! -f "$LOG_FILE" ] || [ "$log_date" != "$TODAY" ]; then
        execution_count=0
        first_start="$NOW_TIMESTAMP"
        total_lines=0
        
        # Créer nouveau fichier de log
        $CAT_BIN > "$LOG_FILE" <<EOF
========================================
DNS SYNC LOG - $TODAY
========================================
Première exécution: $first_start
EOF
        
        $CHOWN_BIN "$OWNER" "$LOG_FILE" 2>/dev/null || \
            sudo $CHOWN_BIN "$OWNER" "$LOG_FILE" 2>/dev/null || true
        $CHMOD_BIN "$PERMS" "$LOG_FILE" 2>/dev/null || \
            sudo $CHMOD_BIN "$PERMS" "$LOG_FILE" 2>/dev/null || true
    fi
    
    # Incrémenter le compteur d'exécution
    execution_count=$((execution_count + 1))
    
    # Stocker les valeurs pour utilisation globale
    export EXECUTION_COUNT=$execution_count
    export FIRST_START="$first_start"
    export TOTAL_LINES_TODAY=$total_lines
}

################################################################################
# Mise à jour du résumé quotidien
################################################################################

update_daily_summary() {
    local new_lines="$1"
    local total_lines=$((TOTAL_LINES_TODAY + new_lines))
    
    # Ajouter l'entrée d'exécution
    $CAT_BIN >> "$LOG_FILE" <<EOF

----------------------------------------
Exécution #$EXECUTION_COUNT - $NOW_TIMESTAMP
----------------------------------------
Lignes extraites: $new_lines
Total lignes extraites: $total_lines
EOF
    
    export TOTAL_LINES_TODAY=$total_lines
}

################################################################################
# Mise à jour du footer du log
################################################################################

update_log_footer() {
    # Note sur les erreurs API si le fichier existe et n'est pas vide
    local error_note=""
    if [ -f "$ERROR_LOG_FILE" ] && [ -s "$ERROR_LOG_FILE" ]; then
        error_note="
NOTE: Des erreurs API ont été enregistrées.
      Consultez le fichier: $ERROR_LOG_FILE
"
    fi
    
    # Cette fonction sera appelée à la fin
    $CAT_BIN >> "$LOG_FILE" <<EOF
$error_note
========================================
RÉSUMÉ DU JOUR
========================================
Date: $TODAY
Première exécution: $FIRST_START
Dernière exécution: $NOW_TIMESTAMP
Nombre d'exécutions: $EXECUTION_COUNT
Total lignes extraites: $TOTAL_LINES_TODAY
========================================
EOF
}

################################################################################
# Gestion des erreurs API dans JSON
################################################################################

log_api_error() {
    local error_message="$1"
    local http_code="$2"
    
    # Créer ou mettre à jour le fichier d'erreurs JSON
    local error_entry
    
    if [ -f "$ERROR_LOG_FILE" ]; then
        # Lire le contenu existant
        local existing_content=$($CAT_BIN "$ERROR_LOG_FILE" 2>/dev/null || echo "[]")
    else
        existing_content="[]"
    fi
    
    # Ajouter la nouvelle erreur
    if [ -x "$JQ_BIN" ]; then
        error_entry=$($ECHO_BIN "$existing_content" | $JQ_BIN --arg date "$NOW_ISO" \
            --arg msg "$error_message" \
            --arg code "$http_code" \
            '. += [{date: $date, message: $msg, http_code: $code}]' 2>/dev/null || echo "[]")
    else
        # Fallback sans jq
        error_entry="[{\"date\":\"$NOW_ISO\",\"message\":\"$error_message\",\"http_code\":\"$http_code\"}]"
    fi
    
    $ECHO_BIN "$error_entry" > "$ERROR_LOG_FILE"
    
    $CHOWN_BIN "$OWNER" "$ERROR_LOG_FILE" 2>/dev/null || \
        sudo $CHOWN_BIN "$OWNER" "$ERROR_LOG_FILE" 2>/dev/null || true
    $CHMOD_BIN "$PERMS" "$ERROR_LOG_FILE" 2>/dev/null || true
}

################################################################################
# Vérification des prérequis
################################################################################

check_prerequisites() {
    local error_count=0
    
    local binaries=("$OPENSSL_BIN" "$CURL_BIN" "$DATE_BIN" "$AWK_BIN" "$WC_BIN" "$STAT_BIN")
    for bin in "${binaries[@]}"; do
        if [ ! -x "$bin" ]; then
            $ECHO_BIN "ERREUR: Binaire manquant ou non exécutable: $bin" >&2
            ((error_count++))
        fi
    done
    
    if [ ! -f "$SOURCE_LOG" ]; then
        $ECHO_BIN "ERREUR: Fichier source introuvable: $SOURCE_LOG" >&2
        ((error_count++))
    elif [ ! -r "$SOURCE_LOG" ]; then
        $ECHO_BIN "ERREUR: Fichier source non lisible: $SOURCE_LOG" >&2
        ((error_count++))
    fi
    
    return $error_count
}

################################################################################
# Création des répertoires
################################################################################

setup_directories() {
    local dirs=("$DEST_DIR" "$ARCHIVE_DIR" "$SECRET_DIR")
    
    for dir in "${dirs[@]}"; do
        if [ ! -d "$dir" ]; then
            log "Création du répertoire: $dir" "INFO"
            
            if ! $MKDIR_BIN -p "$dir" 2>/dev/null; then
                if command -v sudo >/dev/null 2>&1; then
                    sudo $MKDIR_BIN -p "$dir" || {
                        log "Impossible de créer $dir même avec sudo" "ERROR"
                        return 1
                    }
                else
                    log "Impossible de créer $dir (pas de sudo)" "ERROR"
                    return 1
                fi
            fi
        fi
        
        if [ ! -w "$dir" ]; then
            log "Répertoire non accessible en écriture: $dir" "WARN"
            
            if command -v sudo >/dev/null 2>&1; then
                sudo $CHOWN_BIN -R "$OWNER" "$dir" 2>/dev/null || true
                sudo $CHMOD_BIN "$DIR_PERMS" "$dir" 2>/dev/null || true
            fi
            
            if [ ! -w "$dir" ]; then
                log "ERREUR: Toujours pas accessible: $dir" "ERROR"
                return 1
            fi
        fi
    done
    
    return 0
}

################################################################################
# INITIALISATION
################################################################################

if ! check_prerequisites; then
    $ECHO_BIN "ERREUR: Prérequis non satisfaits. Abandon." >&2
    exit 1
fi

if ! setup_directories; then
    $ECHO_BIN "ERREUR: Impossible de créer les répertoires. Abandon." >&2
    exit 1
fi

# Gérer le log quotidien
manage_daily_log

log "Démarrage exécution #$EXECUTION_COUNT" "INFO"

################################################################################
# Extraction DNS - UNIQUEMENT LES LOGS DU JOUR
################################################################################

log "Extraction des logs DNS pour $TODAY uniquement" "INFO"

TEMP_FILE="$DEST_DIR/.pfsense-dns.tmp.$$"

if ! $AWK_BIN -v today="$TODAY" '
    # Vérifier que la ligne commence bien par un timestamp ISO valide
    /^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/ {
        # Extraire uniquement la date (10 premiers caractères)
        date = substr($1, 1, 10)
        
        # Comparer strictement avec la date du jour
        if (date == today) {
            print $0
            count++
        }
    }
    END {
        # Afficher le nombre de lignes extraites sur stderr
        if (count > 0) {
            print "Extracted lines: " count > "/dev/stderr"
        } else {
            print "No lines found for " today > "/dev/stderr"
        }
    }
' "$SOURCE_LOG" > "$TEMP_FILE" 2>&1; then
    log "ERREUR lors de l'extraction AWK" "ERROR"
    rm -f "$TEMP_FILE"
    exit 1
fi

LINE_COUNT=$($WC_BIN -l < "$TEMP_FILE" 2>/dev/null || echo 0)

if [ "$LINE_COUNT" -eq 0 ]; then
    log "ATTENTION: Aucune entrée DNS trouvée pour $TODAY" "WARN"
else
    log "SUCCESS: $LINE_COUNT entrées DNS extraites pour $TODAY" "INFO"
fi

# Mettre à jour le résumé quotidien
update_daily_summary "$LINE_COUNT"

mv "$TEMP_FILE" "$DEST_FILE" || {
    log "ERREUR: Impossible de déplacer le fichier" "ERROR"
    rm -f "$TEMP_FILE"
    exit 1
}

################################################################################
# Permissions fichier DNS
################################################################################

$CHOWN_BIN "$OWNER" "$DEST_FILE" 2>/dev/null || \
    sudo $CHOWN_BIN "$OWNER" "$DEST_FILE" 2>/dev/null || {
    log "AVERTISSEMENT: Impossible de changer le propriétaire de $DEST_FILE" "WARN"
}

$CHMOD_BIN "$PERMS" "$DEST_FILE" 2>/dev/null || \
    sudo $CHMOD_BIN "$PERMS" "$DEST_FILE" 2>/dev/null || {
    log "AVERTISSEMENT: Impossible de changer les permissions de $DEST_FILE" "WARN"
}

################################################################################
# Statistiques JSON
################################################################################

if FILE_SIZE=$($STAT_BIN -c%s "$DEST_FILE" 2>/dev/null); then
    : # Linux OK
elif FILE_SIZE=$($STAT_BIN -f%z "$DEST_FILE" 2>/dev/null); then
    : # BSD/macOS OK
else
    FILE_SIZE=0
    log "AVERTISSEMENT: Impossible de déterminer la taille du fichier" "WARN"
fi

$CAT_BIN > "$STATS_FILE" <<EOF
{
    "date": "$TODAY",
    "timestamp": "$NOW_ISO",
    "execution_count": $EXECUTION_COUNT,
    "first_start": "$FIRST_START",
    "last_execution": "$NOW_TIMESTAMP",
    "total_lines_today": $TOTAL_LINES_TODAY,
    "current_extraction": $LINE_COUNT,
    "file_size": $FILE_SIZE,
    "status": "success"
}
EOF

if [ $? -eq 0 ]; then
    log "Statistiques JSON créées" "INFO"
    
    $CHOWN_BIN "$OWNER" "$STATS_FILE" 2>/dev/null || \
        sudo $CHOWN_BIN "$OWNER" "$STATS_FILE" 2>/dev/null || true
    $CHMOD_BIN "$PERMS" "$STATS_FILE" 2>/dev/null || \
        sudo $CHMOD_BIN "$PERMS" "$STATS_FILE" 2>/dev/null || true
else
    log "ERREUR: Impossible de créer le fichier de statistiques" "ERROR"
fi

################################################################################
# Génération/Lecture du token secret
################################################################################

log "Vérification du token API" "INFO"

# Vérifier si le fichier secret existe déjà et le lire
if [ -f "$SECRET_FILE" ]; then
    log "Fichier secret existant trouvé, lecture du token" "INFO"
    
    # Lire le token existant depuis le fichier PHP
    if [ -r "$SECRET_FILE" ]; then
        # Extraire le token du fichier PHP
        CRON_TOKEN=$(grep -oP "'token'\s*=>\s*'\K[^']+" "$SECRET_FILE" 2>/dev/null || echo "")
        
        if [ -n "$CRON_TOKEN" ] && [ "$CRON_TOKEN" != "error-reading-token" ]; then
            log "Token existant chargé avec succès (début: ${CRON_TOKEN:0:16}...)" "INFO"
        else
            log "ATTENTION: Impossible de lire le token existant, génération d'un nouveau" "WARN"
            CRON_TOKEN=""
        fi
    else
        log "ATTENTION: Fichier secret non lisible, génération d'un nouveau token" "WARN"
        CRON_TOKEN=""
    fi
fi

# Générer un nouveau token uniquement si nécessaire
if [ -z "$CRON_TOKEN" ]; then
    log "Génération d'un nouveau token" "INFO"
    
    if [ ! -x "$OPENSSL_BIN" ]; then
        log "ERREUR: OpenSSL non disponible" "ERROR"
        CRON_TOKEN="fallback-token-$(date +%s)-$$"
    else
        CRON_TOKEN=$($OPENSSL_BIN rand -hex 32 2>/dev/null || echo "error-generating-token")
        
        if [ "$CRON_TOKEN" = "error-generating-token" ]; then
            log "ERREUR: Impossible de générer le token avec openssl" "ERROR"
            CRON_TOKEN="fallback-token-$(date +%s)-$$"
        fi
    fi
    
    # Écrire le nouveau token au format PHP avec permissions LISIBLES par www-data
    (
        umask 027  # rw-r----- (640) - propriétaire: rw, groupe: r, autres: rien
        
        # Créer le contenu PHP
        PHP_CONTENT="<?php
return [
    'token' => '$CRON_TOKEN'
];
"
        
        if ! $ECHO_BIN "$PHP_CONTENT" > "$SECRET_FILE" 2>/dev/null; then
            if command -v sudo >/dev/null 2>&1; then
                $ECHO_BIN "$PHP_CONTENT" | sudo tee "$SECRET_FILE" > /dev/null || {
                    log "ERREUR: Impossible de créer le fichier secret même avec sudo" "ERROR"
                    exit 1
                }
            else
                log "ERREUR: Impossible de créer le fichier secret: $SECRET_FILE" "ERROR"
                exit 1
            fi
        fi
    )
    
    if [ ! -f "$SECRET_FILE" ]; then
        log "ERREUR CRITIQUE: Fichier secret non créé: $SECRET_FILE" "ERROR"
        exit 1
    fi
    
    # Appliquer les permissions
    $CHOWN_BIN "$OWNER" "$SECRET_FILE" 2>/dev/null || \
        sudo $CHOWN_BIN "$OWNER" "$SECRET_FILE" 2>/dev/null || {
        log "AVERTISSEMENT: Impossible de changer le propriétaire du fichier secret" "WARN"
    }
    
    $CHMOD_BIN "$SECRET_PERMS" "$SECRET_FILE" 2>/dev/null || \
        sudo $CHMOD_BIN "$SECRET_PERMS" "$SECRET_FILE" 2>/dev/null || {
        log "AVERTISSEMENT: Impossible de sécuriser les permissions du fichier secret" "WARN"
    }
    
    log "Nouveau token généré avec succès (début: ${CRON_TOKEN:0:16}...)" "INFO"
fi

################################################################################
# NOUVELLE FONCTION: Envoi API avec timeout intelligent
################################################################################

send_api_request() {
    local token="$1"
    local timeout="$2"
    local request_type="${3:-full}"  # "full" ou "check"
    
    log "Envoi API (timeout: ${timeout}s, type: $request_type)" "INFO"
    
    # Préparer le payload JSON
    POST_PAYLOAD=$($CAT_BIN <<EOF
{
    "job": "dns-daily-sync",
    "date": "$TODAY",
    "lines": $LINE_COUNT,
    "file_size": $FILE_SIZE,
    "timestamp": "$NOW_ISO",
    "token": "$token"
}
EOF
)
    
    # Envoyer la requête et capturer la réponse
    RESPONSE=$($CURL_BIN -s \
        --max-time "$timeout" \
        --connect-timeout "$CONNECT_TIMEOUT" \
        -w "\n%{http_code}" \
        -X POST "$API_URL" \
        -H "Authorization: Bearer $token" \
        -H "Content-Type: application/json" \
        -d "$POST_PAYLOAD" 2>/dev/null || echo -e "\n000")
    
    # Séparer le corps de la réponse et le code HTTP
    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    RESPONSE_BODY=$(echo "$RESPONSE" | sed '$d')
    
    # Parser la réponse JSON
    local success="false"
    local already_done="false"
    local message=""
    
    if [ -x "$JQ_BIN" ] && [ -n "$RESPONSE_BODY" ]; then
        success=$($ECHO_BIN "$RESPONSE_BODY" | $JQ_BIN -r '.success // false' 2>/dev/null || echo "false")
        already_done=$($ECHO_BIN "$RESPONSE_BODY" | $JQ_BIN -r '.already_done // false' 2>/dev/null || echo "false")
        message=$($ECHO_BIN "$RESPONSE_BODY" | $JQ_BIN -r '.message // ""' 2>/dev/null || echo "Pas de message")
    else
        # Fallback parsing manuel simple
        if echo "$RESPONSE_BODY" | grep -q '"success"[[:space:]]*:[[:space:]]*true'; then
            success="true"
        fi
        if echo "$RESPONSE_BODY" | grep -q '"already_done"[[:space:]]*:[[:space:]]*true'; then
            already_done="true"
        fi
        message=$(echo "$RESPONSE_BODY" | sed -n 's/.*"message"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
    fi
    
    # Retourner le résultat au format: HTTP_CODE|SUCCESS|ALREADY_DONE|MESSAGE
    echo "$HTTP_CODE|$success|$already_done|$message"
}

################################################################################
# NOUVELLE LOGIQUE: Cycles intelligents avec vérification already_done
################################################################################

log "========================================" "INFO"
log "DÉBUT DE LA SYNCHRONISATION INTELLIGENTE" "INFO"
log "Configuration: Timeout initial ${INITIAL_TIMEOUT}s, Max cycles: $MAX_CYCLES" "INFO"
log "========================================" "INFO"

cycle=0
final_success="false"
final_already_done="false"

while [ $cycle -lt $MAX_CYCLES ] && [ "$final_success" != "true" ]; do
    cycle=$((cycle + 1))
    
    log "----------------------------------------" "INFO"
    log "CYCLE #$cycle/$MAX_CYCLES" "INFO"
    log "----------------------------------------" "INFO"
    
    # ========================================================================
    # ÉTAPE 1: Envoi initial avec TIMEOUT LONG (15 min)
    # ========================================================================
    
    log "Étape 1: Envoi des données (timeout ${INITIAL_TIMEOUT}s)..." "INFO"
    
    result=$(send_api_request "$CRON_TOKEN" "$INITIAL_TIMEOUT" "full")
    
    HTTP_CODE=$(echo "$result" | cut -d'|' -f1)
    api_success=$(echo "$result" | cut -d'|' -f2)
    api_already_done=$(echo "$result" | cut -d'|' -f3)
    api_message=$(echo "$result" | cut -d'|' -f4-)
    
    log "Résultat: HTTP $HTTP_CODE | success=$api_success | already_done=$api_already_done" "INFO"
    
    # ========================================================================
    # ANALYSE DE LA RÉPONSE
    # ========================================================================
    
    case "$HTTP_CODE" in
        200)
            if [ "$api_success" = "true" ]; then
                log "✓ Traitement terminé avec succès!" "INFO"
                log "Message serveur: $api_message" "INFO"
                final_success="true"
                final_already_done="$api_already_done"
                break
            elif [ "$api_already_done" = "true" ]; then
                log "✓ Déjà traité précédemment (already_done=true)" "INFO"
                log "Message serveur: $api_message" "INFO"
                final_success="true"
                final_already_done="true"
                break
            else
                # Success=false, already_done=false = En cours de traitement
                log "⧗ Serveur en cours de traitement (success=false, already_done=false)" "INFO"
                log "Message serveur: $api_message" "INFO"
                
                # Attendre avant de vérifier à nouveau
                if [ $cycle -lt $MAX_CYCLES ]; then
                    log "Attente de ${VERIFICATION_DELAY}s avant vérification..." "INFO"
                    sleep "$VERIFICATION_DELAY"
                    
                    # ========================================================
                    # ÉTAPE 2: VÉRIFICATION si le traitement est terminé
                    # ========================================================
                    
                    log "Étape 2: Vérification du statut (timeout ${CHECK_TIMEOUT}s)..." "INFO"
                    
                    check_result=$(send_api_request "$CRON_TOKEN" "$CHECK_TIMEOUT" "check")
                    
                    check_http=$(echo "$check_result" | cut -d'|' -f1)
                    check_success=$(echo "$check_result" | cut -d'|' -f2)
                    check_already_done=$(echo "$check_result" | cut -d'|' -f3)
                    check_message=$(echo "$check_result" | cut -d'|' -f4-)
                    
                    log "Vérification: HTTP $check_http | success=$check_success | already_done=$check_already_done" "INFO"
                    
                    if [ "$check_http" = "200" ]; then
                        if [ "$check_success" = "true" ] || [ "$check_already_done" = "true" ]; then
                            log "✓ Traitement confirmé terminé!" "INFO"
                            log "Message serveur: $check_message" "INFO"
                            final_success="true"
                            final_already_done="$check_already_done"
                            break
                        else
                            log "⧗ Toujours en cours de traitement" "INFO"
                            
                            if [ $cycle -lt $MAX_CYCLES ]; then
                                log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                                sleep "$RETRY_DELAY"
                            fi
                        fi
                    else
                        log "⚠ Erreur lors de la vérification (HTTP $check_http)" "WARN"
                        log_api_error "Erreur vérification: $check_message" "$check_http"
                        
                        if [ $cycle -lt $MAX_CYCLES ]; then
                            log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                            sleep "$RETRY_DELAY"
                        fi
                    fi
                fi
            fi
            ;;
            
        401|403)
            log "✗ Erreur d'authentification (HTTP $HTTP_CODE)" "ERROR"
            log "Message serveur: $api_message" "ERROR"
            log_api_error "Authentification refusée: $api_message" "$HTTP_CODE"
            break
            ;;
            
        404)
            log "✗ Endpoint API introuvable (HTTP 404)" "ERROR"
            log_api_error "Endpoint non trouvé: $api_message" "$HTTP_CODE"
            break
            ;;
            
        500|502|503)
            log "✗ Erreur serveur (HTTP $HTTP_CODE)" "ERROR"
            log "Message serveur: $api_message" "ERROR"
            log_api_error "Erreur serveur: $api_message" "$HTTP_CODE"
            
            if [ $cycle -lt $MAX_CYCLES ]; then
                log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                sleep "$RETRY_DELAY"
            fi
            ;;
            
        000)
            log "✗ Timeout ou erreur réseau (pas de réponse)" "ERROR"
            log_api_error "Timeout réseau après ${INITIAL_TIMEOUT}s" "$HTTP_CODE"
            
            # Après un timeout, vérifier si le traitement est peut-être terminé
            if [ $cycle -lt $MAX_CYCLES ]; then
                log "Attente de ${VERIFICATION_DELAY}s avant vérification..." "INFO"
                sleep "$VERIFICATION_DELAY"
                
                log "Vérification post-timeout (timeout ${CHECK_TIMEOUT}s)..." "INFO"
                
                check_result=$(send_api_request "$CRON_TOKEN" "$CHECK_TIMEOUT" "check")
                
                check_http=$(echo "$check_result" | cut -d'|' -f1)
                check_success=$(echo "$check_result" | cut -d'|' -f2)
                check_already_done=$(echo "$check_result" | cut -d'|' -f3)
                check_message=$(echo "$check_result" | cut -d'|' -f4-)
                
                if [ "$check_http" = "200" ]; then
                    if [ "$check_success" = "true" ] || [ "$check_already_done" = "true" ]; then
                        log "✓ Le traitement était terminé malgré le timeout initial!" "INFO"
                        log "Message serveur: $check_message" "INFO"
                        final_success="true"
                        final_already_done="$check_already_done"
                        break
                    else
                        log "⧗ Toujours en cours après timeout" "INFO"
                        
                        if [ $cycle -lt $MAX_CYCLES ]; then
                            log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                            sleep "$RETRY_DELAY"
                        fi
                    fi
                else
                    log "⚠ Impossible de vérifier l'état après timeout" "WARN"
                    
                    if [ $cycle -lt $MAX_CYCLES ]; then
                        log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                        sleep "$RETRY_DELAY"
                    fi
                fi
            fi
            ;;
            
        *)
            log "⚠ Réponse inattendue (HTTP $HTTP_CODE)" "WARN"
            log "Message serveur: $api_message" "WARN"
            log_api_error "Code HTTP inattendu: $api_message" "$HTTP_CODE"
            
            if [ $cycle -lt $MAX_CYCLES ]; then
                log "Attente de ${RETRY_DELAY}s avant prochain cycle..." "INFO"
                sleep "$RETRY_DELAY"
            fi
            ;;
    esac
done

################################################################################
# RÉSULTAT FINAL ET ACTIONS
################################################################################

log "========================================" "INFO"
log "FIN DE LA SYNCHRONISATION" "INFO"
log "Cycles effectués: $cycle/$MAX_CYCLES" "INFO"
log "Résultat final: success=$final_success, already_done=$final_already_done" "INFO"
log "========================================" "INFO"

if [ "$final_success" = "true" ]; then
    log "✓✓✓ SYNCHRONISATION RÉUSSIE ✓✓✓" "INFO"
    
    # Créer un fichier marqueur de succès
    SUCCESS_MARKER="$DEST_DIR/.last_sync_success"
    $ECHO_BIN "$NOW_TIMESTAMP" > "$SUCCESS_MARKER" 2>/dev/null || \
        sudo bash -c "echo \"$NOW_TIMESTAMP\" > \"$SUCCESS_MARKER\"" 2>/dev/null || true
    
    # ========================================================================
    # ACTION CRITIQUE: VIDER LE FICHIER pfsense-dns.log (PAS DE ROTATION)
    # ========================================================================
    
    log "Vidage du fichier $DEST_FILE (données synchronisées en BDD)" "INFO"
    
    if > "$DEST_FILE" 2>/dev/null; then
        log "✓ Fichier vidé avec succès" "INFO"
    else
        if command -v sudo >/dev/null 2>&1; then
            if sudo bash -c "> \"$DEST_FILE\"" 2>/dev/null; then
                log "✓ Fichier vidé avec succès (sudo)" "INFO"
            else
                log "✗ ÉCHEC: Impossible de vider le fichier" "ERROR"
            fi
        else
            log "✗ ÉCHEC: Impossible de vider le fichier (pas de sudo)" "ERROR"
        fi
    fi
    
    # Vérification du vidage
    if [ -f "$DEST_FILE" ]; then
        ACTUAL_SIZE=$($STAT_BIN -c%s "$DEST_FILE" 2>/dev/null || $STAT_BIN -f%z "$DEST_FILE" 2>/dev/null || echo -1)
        if [ "$ACTUAL_SIZE" -eq 0 ]; then
            log "✓ CONFIRMATION: Fichier vide (0 octets)" "INFO"
        else
            log "⚠ ATTENTION: Fichier non complètement vide ($ACTUAL_SIZE octets)" "WARN"
        fi
    fi
    
    # ========================================================================
    # Nettoyage des anciennes archives de logs (>3 jours)
    # ========================================================================
    
    log "Nettoyage des archives de logs de synchronisation (>3 jours)" "INFO"
    
    if [ -d "$ARCHIVE_DIR" ]; then
        DELETED_COUNT=0
        while IFS= read -r archive_file; do
            if [ -f "$archive_file" ]; then
                if rm -f "$archive_file" 2>/dev/null; then
                    ((DELETED_COUNT++))
                    log "✓ Supprimé: $(basename "$archive_file")" "INFO"
                elif command -v sudo >/dev/null 2>&1; then
                    if sudo rm -f "$archive_file" 2>/dev/null; then
                        ((DELETED_COUNT++))
                        log "✓ Supprimé (sudo): $(basename "$archive_file")" "INFO"
                    fi
                fi
            fi
        done < <(find "$ARCHIVE_DIR" -name "dns-sync-*.log" -type f -mtime +3 2>/dev/null)
        
        if [ $DELETED_COUNT -gt 0 ]; then
            log "✓ $DELETED_COUNT archive(s) supprimée(s)" "INFO"
        else
            log "ℹ Aucune archive à supprimer (<3 jours)" "INFO"
        fi
        
        ARCHIVE_COUNT=$(find "$ARCHIVE_DIR" -name "dns-sync-*.log" -type f 2>/dev/null | wc -l)
        log "ℹ Archives restantes: $ARCHIVE_COUNT" "INFO"
    fi
    
    # Nettoyer le fichier d'erreurs s'il existe
    if [ -f "$ERROR_LOG_FILE" ]; then
        log "Nettoyage du fichier d'erreurs (synchronisation réussie)" "INFO"
        rm -f "$ERROR_LOG_FILE" 2>/dev/null || sudo rm -f "$ERROR_LOG_FILE" 2>/dev/null || true
    fi
    
else
    log "✗✗✗ ÉCHEC DE LA SYNCHRONISATION ✗✗✗" "ERROR"
    log "⚠ Le fichier $DEST_FILE n'a PAS été vidé (synchronisation échouée)" "WARN"
    log "⚠ Les données restent disponibles pour une prochaine tentative" "WARN"
fi

################################################################################
# Nettoyage final
################################################################################

# Supprimer tous les fichiers temporaires
rm -f "$DEST_DIR"/.pfsense-dns.tmp.* 2>/dev/null || true

# Mettre à jour le footer du log
update_log_footer

log "Script terminé (exécution #$EXECUTION_COUNT)" "INFO"

exit 0