# Local-development MySQL for the UNT Robotics site.
#
# The schema in initdb/01-schema.sql is dumped from production (schema only —
# no member or payment PII). initdb/02-seed.sql adds synthetic test data plus a
# couple of non-sensitive real config rows. Both run only on first init (when
# the data volume is empty).
#
# NOTE: production currently runs MySQL 5.7; this image uses 8.0 (supported).
# The 5.7-dumped schema loads cleanly on 8.0.
FROM mysql:8.0

COPY my.cnf /etc/mysql/conf.d/untrobotics.cnf
COPY initdb/ /docker-entrypoint-initdb.d/
