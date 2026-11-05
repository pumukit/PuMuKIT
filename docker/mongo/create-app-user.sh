#!/bin/bash
set -e

mongosh "$MONGO_INITDB_DATABASE" --quiet --eval 'db.createUser({user: process.env.MONGODB_USER, pwd: process.env.MONGODB_PASSWORD, roles: [{role: "readWrite", db: db.getName()}]})'
