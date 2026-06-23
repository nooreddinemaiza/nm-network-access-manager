#!/bin/bash

set -u

################################################################################
# Configuration
################################################################################

SOURCE_LOG="/var/log/pfsense-dns.log"

DEST_DIR="/var/www/radius/Storage/Logs"
ARCHIVE_DIR="/var/www/radius/Storage/archives"

DEST_FILE="$DEST_DIR/pfsense-dns.log"
LOG_FILE="$DEST_DIR/dns-sync.log"
STATS_FILE="$DEST_DIR/dns-stats.json"
ERROR_LOG_FILE="$DEST_DIR/dns-errors.json"

# Fichier token accessible par www-data (format PHP)
SECRET_FILE="$DEST_DIR/cron_secret.php"

API_URL="http://192.168.0.20/cron/update-log"

# Permissions
OWNER="root:www-data"
PERMS="640"          # rw-r----- (root écrit, www-data lit)
DIR_PERMS="750"      # rwxr-x---
SECRET_PERMS="640"   # www-data peut lire

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
# Logging (nouvelle structure pour le fichier quotidien)
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
                    $ECHO_BIN "📦 Log précédent archivé: $archive_file" >&2
                    # Appliquer les permissions sur l'archive
                    $CHOWN_BIN "$OWNER" "$archive_file" 2>/dev/null || \
                        sudo $CHOWN_BIN "$OWNER" "$archive_file" 2>/dev/null || true
                    $CHMOD_BIN "$PERMS" "$archive_file" 2>/dev/null || \
                        sudo $CHMOD_BIN "$PERMS" "$archive_file" 2>/dev/null || true
                else
                    $ECHO_BIN "⚠️  Impossible d'archiver le log précédent" >&2
                fi
                # Supprimer le marqueur de succès
                rm -f "$success_marker" 2>/dev/null || sudo rm -f "$success_marker" 2>/dev/null || true
            else
                # Pas de sync réussie, supprimer le log sans archiver
                $ECHO_BIN "⚠️  Log précédent supprimé (dernière sync échouée, pas d'archivage)" >&2
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
    # Cette fonction sera appelée à la fin
    $CAT_BIN >> "$LOG_FILE" <<EOF

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
    local dirs=("$DEST_DIR" "$ARCHIVE_DIR")
    
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

log "🚀 Démarrage exécution #$EXECUTION_COUNT" "INFO"

################################################################################
# Extraction DNS
################################################################################

log "🔍 Extraction des logs DNS pour $TODAY" "INFO"

TEMP_FILE="$DEST_DIR/.pfsense-dns.tmp.$$"

if ! $AWK_BIN -v today="$TODAY" '
    $0 ~ /^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}/ {
        date = substr($1, 1, 10)
        if (date == today) {
            print $0
            count++
        }
    }
    END {
        print "Extracted lines: " count > "/dev/stderr"
    }
' "$SOURCE_LOG" > "$TEMP_FILE" 2>&1; then
    log "ERREUR lors de l'extraction AWK" "ERROR"
    rm -f "$TEMP_FILE"
    exit 1
fi

LINE_COUNT=$($WC_BIN -l < "$TEMP_FILE" 2>/dev/null || echo 0)

if [ "$LINE_COUNT" -eq 0 ]; then
    log "⚠️  Aucune entrée DNS trouvée pour $TODAY" "WARN"
else
    log "✅ $LINE_COUNT entrées DNS extraites" "INFO"
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
    log "📊 Statistiques JSON créées" "INFO"
    
    $CHOWN_BIN "$OWNER" "$STATS_FILE" 2>/dev/null || \
        sudo $CHOWN_BIN "$OWNER" "$STATS_FILE" 2>/dev/null || true
    $CHMOD_BIN "$PERMS" "$STATS_FILE" 2>/dev/null || \
        sudo $CHMOD_BIN "$PERMS" "$STATS_FILE" 2>/dev/null || true
else
    log "ERREUR: Impossible de créer le fichier de statistiques" "ERROR"
fi

################################################################################
# Génération du token secret (LISIBLE PAR LE SERVEUR WEB)
################################################################################

log "🔐 Génération du token API" "INFO"

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

# Écrire le token au format PHP avec permissions LISIBLES par www-data
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

log "✅ Token généré: ${CRON_TOKEN:0:16}..." "INFO"

################################################################################
# Envoi POST API avec gestion de retry
################################################################################

send_api_request() {
    local token="$1"
    local attempt="$2"
    
    log "📡 Envoi API (tentative #$attempt)" "INFO"
    
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
    RESPONSE=$($CURL_BIN -s --max-time 10 --connect-timeout 5 \
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
    local message=""
    
    if [ -x "$JQ_BIN" ] && [ -n "$RESPONSE_BODY" ]; then
        success=$($ECHO_BIN "$RESPONSE_BODY" | $JQ_BIN -r '.success // false' 2>/dev/null || echo "false")
        message=$($ECHO_BIN "$RESPONSE_BODY" | $JQ_BIN -r '.message // ""' 2>/dev/null || echo "")
    else
        # Fallback parsing manuel simple
        if echo "$RESPONSE_BODY" | grep -q '"success"[[:space:]]*:[[:space:]]*true'; then
            success="true"
        fi
        message=$(echo "$RESPONSE_BODY" | sed -n 's/.*"message"[[:space:]]*:[[:space:]]*"\([^"]*\)".*/\1/p')
    fi
    
    # Retourner le résultat
    echo "$HTTP_CODE|$success|$message"
}

################################################################################
# Logique principale d'envoi avec retry
################################################################################

MAX_RETRIES=3
retry_count=0
api_success="false"

while [ $retry_count -lt $MAX_RETRIES ] && [ "$api_success" != "true" ]; do
    retry_count=$((retry_count + 1))
    
    # Envoyer la requête
    result=$(send_api_request "$CRON_TOKEN" "$retry_count")
    
    # Parser le résultat
    HTTP_CODE=$(echo "$result" | cut -d'|' -f1)
    api_success=$(echo "$result" | cut -d'|' -f2)
    api_message=$(echo "$result" | cut -d'|' -f3-)
    
    case "$HTTP_CODE" in
        200)
            if [ "$api_success" = "true" ]; then
                log "✅ POST envoyé avec succès (HTTP 200)" "INFO"
                if [ -n "$api_message" ]; then
                    log "Message API: $api_message" "INFO"
                fi
                break
            else
                log "⚠️  API a répondu HTTP 200 mais success=false" "WARN"
                log "Message API: $api_message" "WARN"
                log_api_error "$api_message" "$HTTP_CODE"
                
                # Retry sans régénérer le token
                if [ $retry_count -lt $MAX_RETRIES ]; then
                    log "🔄 Nouvelle tentative dans 5 secondes..." "INFO"
                    sleep 5
                fi
            fi
            ;;
        401)
            log "❌ Échec authentification API (HTTP 401)" "ERROR"
            log_api_error "Authentification refusée: $api_message" "$HTTP_CODE"
            break
            ;;
        403)
            log "❌ Accès refusé API (HTTP 403)" "ERROR"
            log_api_error "Accès interdit: $api_message" "$HTTP_CODE"
            break
            ;;
        404)
            log "❌ Endpoint API introuvable (HTTP 404)" "ERROR"
            log_api_error "Endpoint non trouvé: $api_message" "$HTTP_CODE"
            break
            ;;
        500|502|503)
            log "❌ Erreur serveur API (HTTP $HTTP_CODE)" "ERROR"
            log_api_error "Erreur serveur: $api_message" "$HTTP_CODE"
            
            if [ $retry_count -lt $MAX_RETRIES ]; then
                log "🔄 Nouvelle tentative dans 10 secondes..." "INFO"
                sleep 10
            fi
            ;;
        000)
            log "❌ Échec connexion API (timeout ou réseau)" "ERROR"
            log_api_error "Timeout ou erreur réseau" "$HTTP_CODE"
            
            if [ $retry_count -lt $MAX_RETRIES ]; then
                log "🔄 Nouvelle tentative dans 10 secondes..." "INFO"
                sleep 10
            fi
            ;;
        *)
            log "⚠️  Réponse API inattendue (HTTP $HTTP_CODE)" "WARN"
            log_api_error "Code HTTP inattendu: $api_message" "$HTTP_CODE"
            
            if [ $retry_count -lt $MAX_RETRIES ]; then
                log "🔄 Nouvelle tentative dans 5 secondes..." "INFO"
                sleep 5
            fi
            ;;
    esac
done

# Vérifier le résultat final
if [ "$api_success" = "true" ]; then
    log "✅ Synchronisation API réussie" "INFO"
    
    # Créer un fichier marqueur de succès pour l'archivage du prochain jour
    SUCCESS_MARKER="$DEST_DIR/.last_sync_success"
    $ECHO_BIN "$NOW_TIMESTAMP" > "$SUCCESS_MARKER" 2>/dev/null || \
        sudo bash -c "echo \"$NOW_TIMESTAMP\" > \"$SUCCESS_MARKER\"" 2>/dev/null || true
    
    ################################################################################
    # Actions après succès de la synchronisation
    ################################################################################
    
    # 1. Vider le fichier pfsense-dns.log
    log "🗑️  Vidage du fichier pfsense-dns.log" "INFO"
    
    if > "$DEST_FILE" 2>/dev/null; then
        log "✅ Fichier pfsense-dns.log vidé avec succès" "INFO"
    else
        if command -v sudo >/dev/null 2>&1; then
            sudo bash -c "> \"$DEST_FILE\"" 2>/dev/null && \
                log "✅ Fichier pfsense-dns.log vidé avec succès (sudo)" "INFO" || \
                log "⚠️  Impossible de vider le fichier pfsense-dns.log" "WARN"
        else
            log "⚠️  Impossible de vider le fichier pfsense-dns.log" "WARN"
        fi
    fi
    
    # 2. Nettoyer les archives de plus de 3 jours
    log "🧹 Nettoyage des archives de plus de 3 jours" "INFO"
    
    if [ -d "$ARCHIVE_DIR" ]; then
        # Compter le nombre de fichiers avant nettoyage
        ARCHIVE_COUNT_BEFORE=$(find "$ARCHIVE_DIR" -name "dns-sync-*.log" -type f 2>/dev/null | wc -l)
        
        # Supprimer les fichiers de plus de 3 jours
        DELETED_COUNT=0
        while IFS= read -r archive_file; do
            if [ -f "$archive_file" ]; then
                if rm -f "$archive_file" 2>/dev/null; then
                    ((DELETED_COUNT++))
                    log "🗑️  Supprimé: $(basename "$archive_file")" "INFO"
                elif command -v sudo >/dev/null 2>&1; then
                    if sudo rm -f "$archive_file" 2>/dev/null; then
                        ((DELETED_COUNT++))
                        log "🗑️  Supprimé (sudo): $(basename "$archive_file")" "INFO"
                    fi
                fi
            fi
        done < <(find "$ARCHIVE_DIR" -name "dns-sync-*.log" -type f -mtime +3 2>/dev/null)
        
        if [ $DELETED_COUNT -gt 0 ]; then
            log "✅ $DELETED_COUNT archive(s) supprimée(s)" "INFO"
        else
            log "ℹ️  Aucune archive à supprimer (< 3 jours)" "INFO"
        fi
        
        # Afficher le nombre d'archives restantes
        ARCHIVE_COUNT_AFTER=$(find "$ARCHIVE_DIR" -name "dns-sync-*.log" -type f 2>/dev/null | wc -l)
        log "📊 Archives restantes: $ARCHIVE_COUNT_AFTER" "INFO"
    else
        log "⚠️  Répertoire d'archives introuvable: $ARCHIVE_DIR" "WARN"
    fi
    
else
    log "❌ Échec de la synchronisation API après $retry_count tentative(s)" "ERROR"
    log "⚠️  Le fichier pfsense-dns.log n'a PAS été vidé (synchronisation échouée)" "WARN"
fi

################################################################################
# Nettoyage et fin
################################################################################

rm -f "$DEST_DIR"/.pfsense-dns.tmp.* 2>/dev/null || true

# Mettre à jour le footer du log
update_log_footer

log "Script terminé (exécution #$EXECUTION_COUNT)" "INFO"

exit 0