#!/bin/bash
# Cria uma base de dados por universo, com schema, mapa, itens e utilizador admin.
# Corre só no primeiro arranque do MySQL (volume vazio).
#
# GALAXY_UNIVERSES: lista separada por espaços de  nome:mapa.sql:galaxias.sql:planeta_inicial
set -e

for spec in $GALAXY_UNIVERSES; do
    IFS=: read -r name world galaxies start <<< "$spec"
    db="galaxy_${name}"
    echo "== Universo '$name' (base de dados $db, planeta inicial $start)"

    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<SQL
CREATE DATABASE IF NOT EXISTS \`$db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
GRANT ALL PRIVILEGES ON \`$db\`.* TO '$MYSQL_USER'@'%';
SQL

    mysql=(mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$db")
    for f in install.sql "$world" items.sql "$galaxies"; do
        echo "   a importar $f"
        # como o install.php, ignora queries que falham (o world.sql tem inserts duplicados)
        "${mysql[@]}" --force < "/galaxy-sql/$f"
    done

    "${mysql[@]}" <<SQL
ALTER TABLE galaxy_users ALTER planet SET DEFAULT '$start';
INSERT INTO galaxy_users (active, login, password, usergroup, email, registered, planet)
VALUES (1, '${GALAXY_ADMIN_LOGIN}', MD5('${GALAXY_ADMIN_PASSWORD}'), 'wheel', '${GALAXY_ADMIN_EMAIL}', CURDATE(), '$start');
SQL
done
