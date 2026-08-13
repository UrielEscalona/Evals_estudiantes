**Link:** https://github.com/cuauhtemocbe/agentic-evals

**Propuesta:** Docker first + Poetry + Python 3.13

**Cambios que agregué al entorno base:**

- **Ollama como contenedor propio** en `docker-compose.yml`: un servicio `ollama` (imagen `ollama/ollama`, puerto `11434`, con healthcheck y volumen persistente) del que depende el servicio `api`, para no tener que instalar/correr Ollama en el host.
- **Git hook de pre-commit** (`.githooks/pre-commit`, habilitado con `make install-hooks`): antes de cada commit corre `ruff check` y `ruff format --check` dentro de Docker, para no commitear código con errores de lint o formato.
- **Lint y tipado**: `ruff` (reglas `E`, `F`, `I`, `UP`, formato con comillas dobles) y `mypy` en modo `strict` configurados en `pyproject.toml`, con targets en el `Makefile` (`make lint`, `make format-check`, `make typecheck`).