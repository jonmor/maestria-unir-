package demo;

import com.zaxxer.hikari.HikariConfig;
import com.zaxxer.hikari.HikariDataSource;
import io.javalin.Javalin;
import io.javalin.http.Context;

import java.sql.ResultSet;
import java.sql.SQLException;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

public class App {

    record Todo(int id, String title, boolean done, String lang, String created_at) {
        static Todo from(ResultSet rs) throws SQLException {
            return new Todo(rs.getInt("id"), rs.getString("title"), rs.getBoolean("done"),
                    rs.getString("lang"), rs.getObject("created_at").toString());
        }
    }

    record NewTodo(String title) {}

    public static void main(String[] args) {
        var cfg = new HikariConfig();
        cfg.setJdbcUrl("jdbc:postgresql://%s:5432/%s".formatted(env("DB_HOST"), env("DB_NAME")));
        cfg.setUsername(env("DB_USER"));
        cfg.setPassword(env("DB_PASSWORD"));
        cfg.setMaximumPoolSize(5);
        var ds = new HikariDataSource(cfg);

        Javalin.create()
            .get("/", ctx -> ctx.json(Map.of("message", "Hola mundo desde Java")))
            .get("/todos", ctx -> {
                try (var c = ds.getConnection();
                     var rs = c.createStatement().executeQuery(
                         "SELECT id, title, done, lang, created_at FROM todos ORDER BY id")) {
                    List<Todo> out = new ArrayList<>();
                    while (rs.next()) out.add(Todo.from(rs));
                    ctx.json(out);
                }
            })
            .post("/todos", ctx -> create(ctx, ds))
            .start(8080);
    }

    private static void create(Context ctx, HikariDataSource ds) throws SQLException {
        NewTodo body;
        try {
            body = ctx.bodyAsClass(NewTodo.class);
        } catch (Exception e) {
            body = new NewTodo(null);
        }
        String title = body.title() == null ? "" : body.title().strip();
        if (title.isEmpty()) {
            ctx.status(400).json(Map.of("error", "title es requerido"));
            return;
        }
        try (var c = ds.getConnection();
             var ps = c.prepareStatement(
                 "INSERT INTO todos (title, lang) VALUES (?, 'java') RETURNING id, title, done, lang, created_at")) {
            ps.setString(1, title);
            try (var rs = ps.executeQuery()) {
                rs.next();
                ctx.status(201).json(Todo.from(rs));
            }
        }
    }

    private static String env(String k) {
        var v = System.getenv(k);
        if (v == null) throw new IllegalStateException("Falta variable " + k);
        return v;
    }
}
