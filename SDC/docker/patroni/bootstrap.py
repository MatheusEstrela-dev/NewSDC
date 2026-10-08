#!/opt/patroni/bin/python
import os
import sys

import psycopg2
from psycopg2 import sql
from psycopg2.extensions import parse_dsn


def connect(connection_parameters, database):
    return psycopg2.connect(**{**connection_parameters, "dbname": database})


def main():
    parameters = parse_dsn(sys.argv[1])
    database = os.environ.get("POSTGRES_DB", "sdc")
    connection = connect(parameters, "postgres")
    connection.autocommit = True
    with connection.cursor() as cursor:
        for name in dict.fromkeys(["template_postgis", database]):
            cursor.execute("SELECT 1 FROM pg_database WHERE datname = %s", (name,))
            if cursor.fetchone() is None:
                cursor.execute(sql.SQL("CREATE DATABASE {}{}").format(
                    sql.Identifier(name),
                    sql.SQL(" IS_TEMPLATE true") if name == "template_postgis" else sql.SQL(""),
                ))
    connection.close()

    for name in dict.fromkeys(["template_postgis", database]):
        connection = connect(parameters, name)
        connection.autocommit = True
        with connection.cursor() as cursor:
            for extension in ["postgis", "postgis_topology", "fuzzystrmatch", "postgis_tiger_geocoder"]:
                cursor.execute(sql.SQL("CREATE EXTENSION IF NOT EXISTS {}").format(sql.Identifier(extension)))
        connection.close()


if __name__ == "__main__":
    main()
