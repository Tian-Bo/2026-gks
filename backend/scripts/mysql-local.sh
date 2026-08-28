#!/bin/sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
MYSQL_HOME="$ROOT_DIR/.runtime/mysql/mysql-9.7.1-macos15-arm64"
RUNTIME_DIR="$ROOT_DIR/.runtime/mysql"
DATA_DIR="$RUNTIME_DIR/data"
RUN_DIR="$RUNTIME_DIR/run"
PORT="${MYSQL_PORT:-3307}"
ROOT_PASSWORD_FILE="$RUNTIME_DIR/root-password"

if [ ! -x "$MYSQL_HOME/bin/mysqld" ]; then
  echo "MySQL runtime is not installed at $MYSQL_HOME" >&2
  exit 1
fi

is_running() {
  [ -f "$ROOT_PASSWORD_FILE" ] && MYSQL_PWD="$(<"$ROOT_PASSWORD_FILE")" "$MYSQL_HOME/bin/mysqladmin" --protocol=TCP --host=127.0.0.1 --port="$PORT" --user=root ping --silent >/dev/null 2>&1
}

case "${1:-status}" in
  start)
    if is_running; then
      echo "MySQL is already running on 127.0.0.1:$PORT"
      exit 0
    fi
    mkdir -p "$DATA_DIR" "$RUN_DIR"
    "$MYSQL_HOME/bin/mysqld" --no-defaults --basedir="$MYSQL_HOME" --datadir="$DATA_DIR" --port="$PORT" --bind-address=127.0.0.1 --socket="$RUN_DIR/mysql.sock" --pid-file="$RUN_DIR/mysql.pid" --log-error="$RUN_DIR/mysql.err" --skip-log-bin --daemonize
    for _ in $(seq 1 30); do
      if is_running; then
        echo "MySQL started on 127.0.0.1:$PORT"
        exit 0
      fi
      sleep 1
    done
    cat "$RUN_DIR/mysql.err" >&2
    exit 1
    ;;
  stop)
    if ! is_running; then
      echo "MySQL is not running"
      exit 0
    fi
    MYSQL_PWD="$(<"$ROOT_PASSWORD_FILE")" "$MYSQL_HOME/bin/mysqladmin" --protocol=TCP --host=127.0.0.1 --port="$PORT" --user=root shutdown
    echo "MySQL stopped"
    ;;
  status)
    if is_running; then
      echo "MySQL is running on 127.0.0.1:$PORT"
      exit 0
    fi
    echo "MySQL is not running"
    exit 1
    ;;
  *)
    echo "Usage: $0 {start|stop|status}" >&2
    exit 2
    ;;
esac
