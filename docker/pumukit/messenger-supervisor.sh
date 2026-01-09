#!/bin/bash

echo "[Messenger Supervisor] Starting Messenger workers..."


is_running() {
    ps aux | grep -v grep | grep "messenger:consume" > /dev/null
    return $?
}

start_workers() {
    echo "[Messenger Supervisor] Starting workers..."
    
    nohup php /srv/pumukit/bin/console messenger:consume \
        pumukit.youtube.events \
        --time-limit=3600 \
        --memory-limit=256M \
        -vv \
        >> /srv/pumukit/var/log/messenger.log 2>&1 &
    
    echo "[Messenger Supervisor] Workers started with PID $!"
}

# Loop infinito de supervisión
while true; do
    if ! is_running; then
        echo "[Messenger Supervisor] Workers not running, starting..."
        start_workers
    fi
    
    sleep 30
done
