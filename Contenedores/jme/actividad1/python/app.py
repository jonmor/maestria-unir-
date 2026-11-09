import os
from flask import Flask, jsonify, request
from psycopg_pool import ConnectionPool
from psycopg.rows import dict_row

DSN = (
    f"host={os.environ['DB_HOST']} dbname={os.environ['DB_NAME']} "
    f"user={os.environ['DB_USER']} password={os.environ['DB_PASSWORD']}"
)
pool = ConnectionPool(DSN, min_size=1, max_size=5, kwargs={"row_factory": dict_row})
app = Flask(__name__)


@app.get("/")
def hello():
    return jsonify(message="Hola mundo desde Python")


@app.get("/todos")
def list_todos():
    with pool.connection() as conn:
        rows = conn.execute(
            "SELECT id, title, done, lang, created_at FROM todos ORDER BY id"
        ).fetchall()
    return jsonify(rows)


@app.post("/todos")
def create_todo():
    body = request.get_json(silent=True) or {}
    title = (body.get("title") or "").strip()
    if not title:
        return jsonify(error="title es requerido"), 400
    with pool.connection() as conn:
        row = conn.execute(
            "INSERT INTO todos (title, lang) VALUES (%s, 'python') "
            "RETURNING id, title, done, lang, created_at",
            (title,),
        ).fetchone()
    return jsonify(row), 201
