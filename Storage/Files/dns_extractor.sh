#!/bin/bash

set -euo pipefail  # Arrêt en cas d'erreur, variables non définies, erreurs dans pipes

# Configuration
readonly LOG_SOURCE="/var/log/syslog"
readonly LOG_DEST="/var/log/pfsense-dns.log"
readonly LOG_DEST_OLD="/var/log/pfsense-dns.log.old"
readonly LOCK_FILE="/var/run/pfsense-dns-extractor.lock"
readonly SCRIPT_NAME="$(basename "$0")"
readonly DATE_TODAY="$(date +%Y-%m-%d)"

# Logging interne du script
# readonly SCRIPT_LOG="/var/log/pfsense-dns-extractor.log" 
# | tee -a "$SCRIPT_LOG" >&2

# Fonction de logging
log_message() {
    local level="$1"
    shift
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$level] $*" 
}

# Fonction de nettoyage
cleanup() {
    local exit_code=$?
    if [[ -f "$LOCK_FILE" ]]; then
        rm -f "$LOCK_FILE"
        log_message "INFO" "Lock file supprimé"
    fi
    
    if [[ $exit_code -ne 0 ]]; then
        log_message "ERROR" "Script terminé avec erreur (code: $exit_code)"
    else
        log_message "INFO" "Script terminé avec succès"
    fi
    
    exit "$exit_code"
}

# Trap pour nettoyage automatique
trap cleanup EXIT INT TERM

# Vérification des privilèges root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        log_message "ERROR" "Ce script doit être exécuté en tant que root"
        exit 1
    fi
}

# Gestion du lock pour éviter les exécutions simultanées
acquire_lock() {
    if [[ -f "$LOCK_FILE" ]]; then
        local lock_pid
        lock_pid=$(cat "$LOCK_FILE" 2>/dev/null || echo "unknown")
        
        # Vérifier si le processus est toujours actif
        if ps -p "$lock_pid" > /dev/null 2>&1; then
            log_message "WARN" "Une instance du script est déjà en cours (PID: $lock_pid)"
            exit 0
        else
            log_message "WARN" "Lock file obsolète trouvé, suppression"
            rm -f "$LOCK_FILE"
        fi
    fi
    
    echo $$ > "$LOCK_FILE"
    log_message "INFO" "Lock acquis (PID: $$)"
}


# Extraction des logs DNS
extract_dns_logs() {
    log_message "INFO" "Début de l'extraction des logs DNS"
    
    # Vérifier que le fichier source existe
    if [[ ! -f "$LOG_SOURCE" ]]; then
        log_message "ERROR" "Fichier source introuvable: $LOG_SOURCE"
        exit 1
    fi
    
    # Compteurs
    local count_before=0
    local count_extracted=0
    local count_after=0
    
    if [[ -f "$LOG_DEST" ]]; then
        count_before=$(wc -l < "$LOG_DEST")
    fi
    
    # Fichier temporaire pour l'extraction
    local temp_file
    temp_file=$(mktemp /tmp/pfsense-dns.XXXXXX)
    
    # Extraction avec grep optimisé
    # Pattern: lignes contenant "unbound[" avec la date du jour
    grep "^${DATE_TODAY}" "$LOG_SOURCE" 2>/dev/null | \
        grep "unbound\[" | \
        grep -E "(A IN|AAAA IN|PTR IN)" > "$temp_file" || true
    
    count_extracted=$(wc -l < "$temp_file")
    
    if [[ $count_extracted -eq 0 ]]; then
        log_message "WARN" "Aucun log DNS trouvé pour aujourd'hui ($DATE_TODAY)"
        rm -f "$temp_file"
        return 0
    fi
    
    # Trier et dédupliquer si nécessaire
    if [[ -f "$LOG_DEST" ]] && [[ -s "$LOG_DEST" ]]; then
        # Fusionner avec les logs existants, trier et dédupliquer
        cat "$LOG_DEST" "$temp_file" | \
            sort -u > "${temp_file}.sorted"
        mv "${temp_file}.sorted" "$LOG_DEST"
    else
        # Simplement trier les nouveaux logs
        sort -u "$temp_file" > "$LOG_DEST"
    fi
    
    rm -f "$temp_file"
    
    count_after=$(wc -l < "$LOG_DEST")
    local count_new=$((count_after - count_before))
    
    log_message "INFO" "Extraction terminée: $count_new nouveaux logs (total: $count_after)"
    
    # Vérifier la taille du fichier
    local file_size
    file_size=$(du -h "$LOG_DEST" | cut -f1)
    log_message "INFO" "Taille du fichier: $file_size"
}

# Validation du format des logs
validate_logs() {
    if [[ ! -f "$LOG_DEST" ]] || [[ ! -s "$LOG_DEST" ]]; then
        return 0
    fi
    
    # Vérifier quelques lignes aléatoires
    local sample_size=10
    local total_lines
    total_lines=$(wc -l < "$LOG_DEST")
    
    if [[ $total_lines -lt $sample_size ]]; then
        sample_size=$total_lines
    fi
    
    local invalid_count=0
    
    while IFS= read -r line; do
        # Vérifier le format: YYYY-MM-DDTHH:MM:SS+00:00 _gateway unbound[PID]: ...
        if ! echo "$line" | grep -qE '^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2} _gateway unbound\[\d+\]:'; then
            ((invalid_count++))
        fi
    done < <(shuf -n "$sample_size" "$LOG_DEST" 2>/dev/null)
    
    if [[ $invalid_count -gt 0 ]]; then
        log_message "WARN" "Format invalide détecté dans $invalid_count/$sample_size lignes échantillonnées"
    else
        log_message "INFO" "Validation du format: OK ($sample_size lignes vérifiées)"
    fi
}

# Fonction principale
main() {
    log_message "INFO" "========== Démarrage de l'extraction DNS =========="
    log_message "INFO" "Date actuelle: $DATE_TODAY"
    
    check_root
    acquire_lock
    extract_dns_logs
    validate_logs
    
    log_message "INFO" "========== Extraction DNS terminée =========="
}

# Exécution
main "$@"