CREATE TABLE jobs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    uuid CHAR(36) NOT NULL,
    type VARCHAR(100) NOT NULL,
    reference_date DATE NULL,

    payload JSON NULL,

    status ENUM('pending','processing','completed','failed','cancelled') 
        NOT NULL DEFAULT 'pending',

    attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 3,

    locked_at DATETIME NULL,
    locked_by VARCHAR(100) NULL,

    started_at DATETIME NULL,
    finished_at DATETIME NULL,

    error_message TEXT NULL,

    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,

    UNIQUE KEY unique_job (type, reference_date),
    UNIQUE KEY unique_uuid (uuid),
    INDEX idx_status (status),
    INDEX idx_locked (locked_at)
);

CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(64),
    username VARCHAR(64) UNIQUE,
    email VARCHAR(120) NOT NULL UNIQUE,
    password_crypt VARCHAR(255) NOT NULL,
    password VARCHAR(255) NOT NULL,
    type ENUM('root', 'moderator') NOT NULL DEFAULT 'moderator',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(64) NOT NULL UNIQUE,
    fullname VARCHAR(100),
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended', 'expired') NOT NULL DEFAULT 'active',
    expires_at DATETIME NOT NULL DEFAULT (DATE_ADD(NOW(), INTERVAL 3 MONTH)),
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE groupes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive','expired') NOT NULL DEFAULT 'active',
    max_members int(64) not null default 40,
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
CREATE TABLE userGroup (
    user_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id, group_id),

    CONSTRAINT fk_user_group_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_user_group_group
        FOREIGN KEY (group_id)
        REFERENCES groupes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;
CREATE TABLE groupInvites (
    id INT AUTO_INCREMENT PRIMARY KEY,

    group_id INT NOT NULL,

    token CHAR(64) NOT NULL UNIQUE, -- SHA-256 hex

    max_uses INT NOT NULL DEFAULT 1,
    used_count INT NOT NULL DEFAULT 0,

    expires_at DATETIME NOT NULL,

    status ENUM('active', 'expired', 'revoked') DEFAULT 'active',

    created_by INT NULL, -- manager/admin
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    last_used_at DATETIME NULL,

    CONSTRAINT fk_invite_group
        FOREIGN KEY (group_id) REFERENCES groupes(id)
        ON DELETE CASCADE
);

CREATE TABLE adminGroup (
    admin_id INT NOT NULL,
    group_id INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (admin_id, group_id),

    CONSTRAINT fk_admin_group_admin
        FOREIGN KEY (admin_id)
        REFERENCES admins(id)
        ON DELETE CASCADE,

    CONSTRAINT fk_admin_group_group
        FOREIGN KEY (group_id)
        REFERENCES groupes(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_policies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_timeout INT NULL,
    daily_time_limit INT NULL,
    total_time_limit INT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_user_policy_user
        FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    CONSTRAINT uq_user_policy UNIQUE (user_id)
) ENGINE=InnoDB;
CREATE TABLE radcheck_user (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    user_id INT NOT NULL,
    attribute VARCHAR(64) NOT NULL,
    op VARCHAR(8) NOT NULL DEFAULT ':=',
    value VARCHAR(255) NOT NULL,

    enabled TINYINT(1) NOT NULL DEFAULT 1,

    reason VARCHAR(255) NULL,
    expires_at DATETIME NULL,

    created_by BIGINT UNSIGNED NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,

    INDEX idx_user (user_id),
    INDEX idx_attr (attribute),

    CONSTRAINT fk_radcheck_user_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
);

CREATE TABLE radreply_user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    attribute VARCHAR(64) NOT NULL,
    op CHAR(2) NOT NULL DEFAULT ':=',
    value VARCHAR(253) NOT NULL,
    priority INT NOT NULL DEFAULT 100,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_radreply_user_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_attribute (attribute),
    INDEX idx_enabled (enabled),
    INDEX idx_priority (priority)
) ENGINE=InnoDB;

CREATE TABLE policy_presets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(64) NOT NULL UNIQUE,
    description VARCHAR(255) NULL,
    status ENUM('active','inactive') NOT NULL DEFAULT 'active',
    expires_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE policy_preset_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    preset_id INT NOT NULL,
    attribute VARCHAR(64) NOT NULL,
    op CHAR(2) NOT NULL DEFAULT ':=',
    value VARCHAR(253) NOT NULL,
    priority INT NOT NULL DEFAULT 50,
    enabled TINYINT(1) NOT NULL DEFAULT 1,

    CONSTRAINT fk_preset_item_preset FOREIGN KEY (preset_id)
        REFERENCES policy_presets(id)
        ON DELETE CASCADE,

    INDEX idx_preset (preset_id),
    INDEX idx_enabled (enabled),
    INDEX idx_priority (priority)
);
CREATE TABLE user_applied_policies (
    user_id INT NOT NULL,
    preset_id INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, preset_id),

    CONSTRAINT fk_user_applied_policy_user FOREIGN KEY (user_id)
        REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_user_applied_policy_preset FOREIGN KEY (preset_id)
        REFERENCES policy_presets(id) ON DELETE CASCADE
);
CREATE TABLE group_applied_policies (
    group_id INT NOT NULL,
    preset_id INT NOT NULL,
    assigned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (group_id, preset_id),
    FOREIGN KEY (group_id) REFERENCES groupes(id) ON DELETE CASCADE,
    FOREIGN KEY (preset_id) REFERENCES policy_presets(id) ON DELETE CASCADE
);
-- Expirimentations
CREATE TABLE dns_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    source_ip VARCHAR(45) NOT NULL,
    domain VARCHAR(255) NOT NULL,

    query_type VARCHAR(10) NULL, 
    transport ENUM('udp','tcp') NULL,

    logged_at DATETIME NOT NULL,

    INDEX idx_domain (domain),
    INDEX idx_logged_at (logged_at),
    INDEX idx_domain_time (domain, logged_at)
);
CREATE TABLE dns_domains_excluded (
    domain VARCHAR(255) PRIMARY KEY
);
CREATE VIEW dns_top_today AS
    SELECT
        domain,
        COUNT(*) AS hits
    FROM dns_logs
    WHERE DATE(logged_at) = CURDATE() 
    GROUP BY domain
    ORDER BY hits DESC;

CREATE VIEW dns_top_7days AS
    SELECT
        domain,
        COUNT(*) AS hits
    FROM dns_logs
    WHERE logged_at >= NOW() - INTERVAL 7 DAY 
    GROUP BY domain
    ORDER BY hits DESC;

CREATE VIEW dns_top_month AS
    SELECT
        domain,
        COUNT(*) AS hits
    FROM dns_logs
    WHERE YEAR(logged_at) = YEAR(NOW())
    AND MONTH(logged_at) = MONTH(NOW())
    
    GROUP BY domain
    ORDER BY hits DESC;

CREATE VIEW dns_daily_stats AS
    SELECT
        DATE(logged_at) AS day,
        COUNT(*) AS total_queries,
        COUNT(DISTINCT domain) AS unique_domains
    FROM dns_logs
    GROUP BY day
    ORDER BY day ASC;
--FIN Exp--------------------------------------------------

-- Table principale des sites
CREATE TABLE sites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    domain VARCHAR(255) NOT NULL UNIQUE,
    first_seen DATE NOT NULL,
    last_seen DATE NOT NULL,
    total_visits BIGINT DEFAULT 0,
    INDEX idx_domain (domain),
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB;

-- Journal quotidien des visites 
CREATE TABLE site_journal (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    site_id INT NOT NULL,
    visit_date DATE NOT NULL,
    visit_count INT DEFAULT 0,
    UNIQUE KEY unique_site_date (site_id, visit_date),
    INDEX idx_date (visit_date),
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-----------------------------------------------------------------------------------------------
-- =============================================================================
-- Core Queue — Database Driver Schema
-- =============================================================================

-- -----------------------------------------------------------------------------
-- Table principale des Jobs en attente / en cours
-- -----------------------------------------------------------------------------

-- MySQL / MariaDB
CREATE TABLE IF NOT EXISTS CREATE TABLE jobs (
    id            VARCHAR(64)   NOT NULL,
    queue         VARCHAR(128)  NOT NULL,
    job_class     VARCHAR(512)  NOT NULL,
    encoded_data  LONGTEXT  NOT NULL,
    attempts      INT UNSIGNED  NOT NULL DEFAULT 0,
    max_attempts  INT UNSIGNED  NOT NULL DEFAULT 3,
    timeout       INT UNSIGNED  NOT NULL DEFAULT 60,
    available_at  INT UNSIGNED  NOT NULL,
    created_at    INT UNSIGNED  NOT NULL,
    status        ENUM('pending','processing')  NOT NULL DEFAULT 'pending',
    reserved_at   INT UNSIGNED  NULL DEFAULT NULL,

    PRIMARY KEY (id),

    -- Index principal utilisé par pop() : queue + status + disponibilité
    INDEX idx_queue_pop     (queue, status, available_at),

    -- Index pour la libération des réservations expirées
    INDEX idx_reserved_at   (queue, status, reserved_at)

);


-- MySQL / MariaDB
CREATE TABLE IF NOT EXISTS failed_jobs (
    id            VARCHAR(64)                    NOT NULL,
    queue         VARCHAR(128)                   NOT NULL,
    job_class     VARCHAR(512)                   NOT NULL,
    encoded_data  LONGTEXT                       NOT NULL,
    attempts      INT UNSIGNED                   NOT NULL DEFAULT 0,
    status        ENUM('failed')                 NOT NULL DEFAULT 'failed',
    failed_at     INT UNSIGNED                   NOT NULL,
    failed_reason TEXT                           NULL DEFAULT NULL,

    PRIMARY KEY (id),
    INDEX idx_failed_queue (queue),
    INDEX idx_failed_class (job_class(191)),
    INDEX idx_failed_at    (failed_at)
);

CREATE TABLE IF NOT EXISTS job_locks (
    lock_key    VARCHAR(191)  NOT NULL,
    locked_at   INT UNSIGNED  NOT NULL,
    expires_at  INT UNSIGNED  NOT NULL,

    PRIMARY KEY (lock_key),
    INDEX idx_expires_at (expires_at)
);
-----------------------------------------------------------------------------------------------
-----------------------------------------------------------------------------------------------


CREATE OR REPLACE VIEW radcheck_view AS
        /* Utilisateurs */
        SELECT
            u.id AS id,
            u.username COLLATE utf8mb4_unicode_ci AS username,
            'Crypt-Password' COLLATE utf8mb4_unicode_ci AS attribute,
            ':=' AS op,
            u.password_hash COLLATE utf8mb4_unicode_ci AS value
        FROM users u
        WHERE
            u.status = 'active'
            AND (u.expires_at IS NULL OR u.expires_at > NOW())

        UNION ALL

        /* Admins */
        SELECT
            a.id AS id,
            a.username COLLATE utf8mb4_unicode_ci AS username,
            'Crypt-Password' COLLATE utf8mb4_unicode_ci AS attribute,
            ':=' AS op,
            a.password_crypt COLLATE utf8mb4_unicode_ci AS value
        FROM admins a
        WHERE a.status = 'active';

CREATE OR REPLACE VIEW active_sessions AS
    SELECT
    r.acctsessionid,
    r.username,
    r.callingstationid AS mac_address,
    r.framedipaddress AS ip_address,
    r.acctstarttime,
    TIMESTAMPDIFF(SECOND, r.acctstarttime, NOW()) AS current_duration
    FROM radacct r
    WHERE r.acctstoptime IS NULL;
CREATE OR REPLACE VIEW session_history AS
    SELECT
        r.username,
        r.callingstationid AS mac_address,
        r.framedipaddress AS ip_address,
        r.acctstarttime,
        r.acctstoptime,
        r.acctsessiontime,
        r.acctterminatecause
    FROM radacct r
    WHERE r.acctstoptime IS NOT NULL
    ORDER BY r.acctstarttime DESC;
CREATE OR REPLACE VIEW user_activity AS
    SELECT
        u.id,
        u.username,
        u.status,
        u.expires_at,
        COUNT(r.acctsessionid) AS total_sessions,
        IFNULL(SUM(r.acctsessiontime), 0) AS total_time
    FROM users u
    LEFT JOIN radacct r ON r.username = u.username
    GROUP BY u.id;
CREATE OR REPLACE VIEW user_stats AS
    SELECT
        u.id,
        u.username,
        u.status,
        u.expires_at,

        COUNT(r.acctsessionid) AS total_sessions,
        IFNULL(SUM(r.acctsessiontime), 0) AS total_time,
        MAX(r.acctstarttime) AS last_login_at,
        AVG(r.acctsessiontime) AS avg_session_time,
        COUNT(DISTINCT r.callingstationid) AS unique_devices,

        (
            SUM(
                CASE
                    WHEN r.acctsessionid IS NOT NULL
                    AND r.acctstoptime IS NULL
                    THEN 1
                    ELSE 0
                END
            ) > 0
        ) AS is_online

    FROM users u
    LEFT JOIN radacct r
        ON r.username COLLATE utf8mb4_unicode_ci
        = u.username COLLATE utf8mb4_unicode_ci

    GROUP BY u.id;

CREATE OR REPLACE VIEW group_stats AS
    
    SELECT
            g.id AS group_id,
            g.name AS group_name,
            COUNT(DISTINCT ug.user_id) AS total_users,
            COUNT(DISTINCT CASE WHEN u.status = 'active' THEN u.id END) AS active_users,
            SUM(
                CASE
                    WHEN r.acctsessionid IS NOT NULL
                    AND r.acctstoptime IS NULL
                    THEN 1
                    ELSE 0
                END
            ) > 0 AS is_online,
            COUNT(r.acctsessionid) AS total_sessions,
            IFNULL(SUM(r.acctsessiontime), 0) AS total_time,
                    IFNULL(SUM(
                        IFNULL(r.acctinputoctets, 0) +
                        IFNULL(r.acctoutputoctets, 0)
                    ), 0) AS total_consumption,
                    IFNULL(SUM(r.acctinputoctets), 0)  AS total_download,
                    IFNULL(SUM(r.acctoutputoctets), 0) AS total_upload
        FROM `groupes` g
        LEFT JOIN userGroup ug ON ug.group_id = g.id
        LEFT JOIN users u ON u.id = ug.user_id
        LEFT JOIN radacct r ON r.username COLLATE utf8mb4_unicode_ci = u.username
        GROUP BY g.id, g.name;
        
CREATE OR REPLACE VIEW live_sessions AS
    SELECT
        r.acctsessionid,
        u.username,
        g.name AS group_name,
        r.callingstationid AS mac_address,
        r.framedipaddress AS ip_address,
        r.acctstarttime,
        TIMESTAMPDIFF(SECOND, r.acctstarttime, NOW()) AS duration
    FROM radacct r
    JOIN users u ON u.username = r.username
    LEFT JOIN userGroup ug ON ug.user_id = u.id
    LEFT JOIN groupes g ON g.id = ug.group_id
    WHERE r.acctstoptime IS NULL;
CREATE OR REPLACE VIEW user_consumption AS
        SELECT
            username,

            SUM(acctinputoctets)  AS total_download,
            SUM(acctoutputoctets) AS total_upload,

            SUM(acctinputoctets + acctoutputoctets) AS total_consumption,

            SUM(acctsessiontime) AS total_time

        FROM radacct
        GROUP BY username;
CREATE OR REPLACE VIEW active_user_groups AS
    SELECT
        ug.user_id,
        g.id AS group_id,
        g.name
    FROM userGroup ug
    JOIN groupes g ON g.id = ug.group_id
    WHERE g.status = 'active'
    AND (g.expires_at IS NULL OR g.expires_at > NOW());
CREATE OR REPLACE VIEW radreply_view AS
    SELECT
        ROW_NUMBER() OVER (ORDER BY username, attribute, priority DESC) AS id,
        username,
        attribute,
        op,
        value
    FROM radreply_effective_view;
CREATE OR REPLACE VIEW radreply_preset_view AS
    SELECT
        u.id            AS id,
        u.username      AS username,

        ppi.attribute   AS attribute,
        ppi.op          AS op,
        ppi.value       AS value,

        ppi.priority    AS priority

    FROM users u
    JOIN user_applied_policies uap
        ON uap.user_id = u.id

    JOIN policy_presets pp
        ON pp.id = uap.preset_id
        AND pp.status = 'active'
        AND (pp.expires_at IS NULL OR pp.expires_at > NOW())

    JOIN policy_preset_items ppi
        ON ppi.preset_id = pp.id
        AND ppi.enabled = 1

    WHERE
        u.status = 'active'
        AND (u.expires_at IS NULL OR u.expires_at > NOW());
CREATE OR REPLACE VIEW radreply_effective_view AS
        SELECT
                t.username,
                t.attribute,
                t.op,
                t.value
            FROM (
                SELECT
                    t.*,
                    ROW_NUMBER() OVER (
                        PARTITION BY t.username, t.attribute 
                        ORDER BY t.final_priority ASC
                    ) AS rn
                FROM (

                    /* 1️⃣ Presets UTILISATEUR (normal + special) */
                    SELECT
                        u.username COLLATE utf8mb4_unicode_ci AS username,
                        ppi.attribute COLLATE utf8mb4_unicode_ci AS attribute,
                        ppi.op COLLATE utf8mb4_unicode_ci AS op,
                        ppi.value COLLATE utf8mb4_unicode_ci AS value,
                        ppi.priority AS priority,
                        CASE
                            WHEN uap.scope = 'special' THEN ppi.priority
                            ELSE ppi.priority + 0.5
                        END AS final_priority,
                        uap.scope AS scope,
                        u.id AS user_id
                    FROM user_applied_policies uap
                    JOIN policy_presets pp ON pp.id = uap.preset_id
                        AND pp.status = 'active'
                        AND (pp.expires_at IS NULL OR pp.expires_at > NOW())
                    JOIN policy_preset_items ppi ON ppi.preset_id = pp.id
                        AND ppi.enabled = 1
                    JOIN users u ON u.id = uap.user_id
                        AND u.status = 'active'
                        AND (u.expires_at IS NULL OR u.expires_at > NOW())

                    UNION ALL

                    /* 2️⃣ Presets de GROUPE (seulement si l'utilisateur n'a PAS de preset 'special') */
                    SELECT
                        u.username COLLATE utf8mb4_unicode_ci AS username,
                        ppi.attribute COLLATE utf8mb4_unicode_ci AS attribute,
                        ppi.op COLLATE utf8mb4_unicode_ci AS op,
                        ppi.value COLLATE utf8mb4_unicode_ci AS value,
                        ppi.priority AS priority,
                        ppi.priority AS final_priority,
                        'normal' AS scope,
                        u.id AS user_id
                    FROM userGroup ug
                    JOIN group_applied_policies gap ON gap.group_id = ug.group_id
                    JOIN policy_presets pp ON pp.id = gap.preset_id
                        AND pp.status = 'active'
                        AND (pp.expires_at IS NULL OR pp.expires_at > NOW())
                    JOIN policy_preset_items ppi ON ppi.preset_id = pp.id
                        AND ppi.enabled = 1
                    JOIN users u ON u.id = ug.user_id
                        AND u.status = 'active'
                        AND (u.expires_at IS NULL OR u.expires_at > NOW())
                    WHERE NOT EXISTS (
                        -- Exclure si l'utilisateur a une politique 'special'
                        SELECT 1 
                        FROM user_applied_policies uap2
                        WHERE uap2.user_id = u.id 
                        AND uap2.scope = 'special'
                    )

                ) t
            ) t
            WHERE t.rn = 1;

CREATE INDEX idx_radacct_username ON radacct(username);
CREATE INDEX idx_radacct_start ON radacct(acctstarttime);
CREATE INDEX idx_radacct_stop ON radacct(acctstoptime);
CREATE INDEX idx_usergroup_group ON userGroup(group_id);

