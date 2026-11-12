const express = require("express");
const { Pool } = require("pg");

const pool = new Pool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  max: 5,
});

const app = express();
app.use(express.json());

app.get("/", (_req, res) => res.json({ message: "Hola mundo desde Node.js" }));

app.get("/todos", async (_req, res, next) => {
  try {
    const { rows } = await pool.query(
      "SELECT id, title, done, lang, created_at FROM todos ORDER BY id"
    );
    res.json(rows);
  } catch (e) { next(e); }
});

app.post("/todos", async (req, res, next) => {
  const title = (req.body?.title ?? "").trim();
  if (!title) return res.status(400).json({ error: "title es requerido" });
  try {
    const { rows } = await pool.query(
      "INSERT INTO todos (title, lang) VALUES ($1, 'node') RETURNING id, title, done, lang, created_at",
      [title]
    );
    res.status(201).json(rows[0]);
  } catch (e) { next(e); }
});

app.listen(8080, "0.0.0.0", () => console.log("node escuchando en :8080"));
