#!/bin/bash

set -u

################################################################################
# Configuration
################################################################################

SECRET_DIR="/var/www/radius/Storage/Files"
SECRET_FILE="$SECRET_DIR/cron_secret.php"

LOG_DIR="/var/www/radius/Storage/Logs"
LOG_FILE="$LOG_DIR/workers-sync.log"
ERROR_LOG="$LOG_DIR/workers-sync-error.log"

API_BASE="http://192.168.0.20"
MAIN_ENDPOINT="/workers/main"
RUN_ENDPOINT="/workers/run"

OPENSSL_BIN="/usr/bin/openssl"
CURL_BIN="/usr/bin/curl"
DATE_BIN="/bin/date"
MKDIR_BIN="/bin/mkdir"
CHOWN_BIN="/bin/chown"
CHMOD_BIN="/bin/chmod"
ECHO_BIN="/bin/echo"

OWNER="root:www-data"
DIR_PERMS="770"
FILE_PERMS="640"

TODAY="$($DATE_BIN '+%Y-%m-%d')"
NOW="$($DATE_BIN '+%Y-%m-%d %H:%M:%S')"

################################################################################
# Logging
################################################################################

log() {
    local msg="$1"
    local lvl="${2:-INFO}"
    local ts="$($DATE_BIN '+%Y-%m-%d %H:%M:%S')"
    $ECHO_BIN "[$ts] [$lvl] $msg" >> "$LOG_FILE"
}

log_error() {
    local msg="$1"
    local ts="$($DATE_BIN '+%Y-%m-%d %H:%M:%S')"
    $ECHO_BIN "[$ts] [ERROR] $msg" >> "$ERROR_LOG"
}

################################################################################
# Setup
################################################################################

setup_directories() {
    for dir in "$SECRET_DIR" "$LOG_DIR"; do
        if [ ! -d "$dir" ]; then
            $MKDIR_BIN -p "$dir" || {
                echo "Impossible de créer $dir"
                exit 1
            }
        fi

        $CHOWN_BIN "$OWNER" "$dir" 2>/dev/null || true
        $CHMOD_BIN "$DIR_PERMS" "$dir" 2>/dev/null || true
    done

    touch "$LOG_FILE" "$ERROR_LOG"
    $CHOWN_BIN "$OWNER" "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || true
    $CHMOD_BIN "$FILE_PERMS" "$LOG_FILE" "$ERROR_LOG" 2>/dev/null || true
}

################################################################################
# Token
################################################################################

generate_token() {
    log "Génération d’un nouveau token"

    if [ ! -x "$OPENSSL_BIN" ]; then
        log_error "OpenSSL introuvable"
        exit 1
    fi

    CRON_TOKEN=$($OPENSSL_BIN rand -hex 32 2>/dev/null)

    if [ -z "$CRON_TOKEN" ]; then
        log_error "Échec génération token"
        exit 1
    fi

    PHP_CONTENT="<?php
return [
    'token' => '$CRON_TOKEN'
];
"

    $ECHO_BIN "$PHP_CONTENT" > "$SECRET_FILE" || {
        log_error "Impossible d’écrire le fichier token"
        exit 1
    }

    $CHOWN_BIN "$OWNER" "$SECRET_FILE" 2>/dev/null || true
    $CHMOD_BIN "$FILE_PERMS" "$SECRET_FILE" 2>/dev/null || true

    log "Token généré et enregistré"
}

################################################################################
# API Call
################################################################################

call_api() {
    local endpoint="$1"
    local job_name="$2"

    PAYLOAD=$(cat <<EOF
{
  "job": "$job_name",
  "token": "$CRON_TOKEN",
  "date": "$TODAY"
}
EOF
)

    log "POST ${API_BASE}${endpoint}"
    log "Payload: $PAYLOAD"

    RESPONSE=$($CURL_BIN -s \
        -w "\n%{http_code}" \
        -X POST "${API_BASE}${endpoint}" \
        -H "Authorization: Bearer $CRON_TOKEN" \
        -H "Content-Type: application/json" \
        -d "$PAYLOAD" 2>/dev/null || echo -e "\n000")

    HTTP_CODE=$(echo "$RESPONSE" | tail -n1)
    BODY=$(echo "$RESPONSE" | sed '$d')

    log "HTTP_CODE=$HTTP_CODE"
    log "BODY=$BODY"

    if [ "$HTTP_CODE" = "000" ]; then
        log_error "Erreur réseau ou timeout vers $endpoint"
    fi
}

################################################################################
# MAIN FLOW
################################################################################

setup_directories

log "========================================"
log "DÉBUT EXÉCUTION"
log "Date: $TODAY"
log "========================================"

generate_token

###############################################################################
# STEP 1 - /workers/main
# job = default
###############################################################################
###############################################################################
# STEP 1 - /workers/main
# job = default
###############################################################################

call_api "$MAIN_ENDPOINT" "default"

# Changement ici : vérifier HTTP 200 et success=true au lieu de 202 et status=true
if [ "$HTTP_CODE" = "200" ] && echo "$BODY" | grep -q '"success"[[:space:]]*:[[:space:]]*true'; then

    log "Condition validée (success=true + HTTP 200)"
    log "Déclenchement de /workers/run"

    ############################################################################
    # STEP 2 - /workers/run
    # job = dns-processing
    ############################################################################

    call_api "$MAIN_ENDPOINT" "dns-processing"

    if echo "$BODY" | grep -q '"success"'; then
        log "Réponse RUN reçue (JSON success/message)"
    else
        log_error "Réponse RUN invalide ou inattendue"
    fi

else
    log "Condition non remplie → arrêt du workflow (HTTP_CODE=$HTTP_CODE, BODY=$BODY)"
fi
log "========================================"
log "FIN EXÉCUTION"
log "========================================"

exit 0
