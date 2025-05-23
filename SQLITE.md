Aqui estão alguns dos principais comandos do SQLite3 formatados em **Markdown**:

```sql
-- Criar um banco de dados
sqlite3 data/database.sqlite

.exit
.help
.tables

-- Criar uma tabela
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nome TEXT NOT NULL,
    email TEXT UNIQUE NOT NULL
);

-- Inserir dados na tabela
INSERT INTO usuarios (nome, email) VALUES ('João', 'joao@email.com');

-- Selecionar todos os registros
SELECT * FROM usuarios;

-- Filtrar resultados com WHERE
SELECT * FROM usuarios WHERE nome = 'João';

-- Atualizar registros
UPDATE usuarios SET email = 'novo@email.com' WHERE nome = 'João';

-- Deletar registros
DELETE FROM usuarios WHERE nome = 'João';

-- Adicionar uma nova coluna
ALTER TABLE usuarios ADD COLUMN idade INTEGER;

-- Criar um índice para otimizar buscas
CREATE INDEX idx_email ON usuarios(email);

-- Exibir a estrutura da tabela
PRAGMA table_info(usuarios);

-- Exportar banco para um arquivo SQL
.dump

-- Importar um arquivo SQL para o banco de dados
.read arquivo.sql

-- Sair do SQLite3
.exit
```

Se precisar de mais detalhes sobre algum desses comandos, me avise! 🚀
