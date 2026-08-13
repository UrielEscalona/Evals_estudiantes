# Sobre este espacio

**Repositorio base:** [agentic-evals](https://github.com/cuauhtemocbe/agentic-evals)

**Propuesta:** Docker first + Poetry + Python 3.13

## Cambios al entorno base

| Cambio | Descripción |
|---|---|
| Ollama como contenedor propio | Servicio `ollama` en `docker-compose.yml` (imagen `ollama/ollama`, puerto `11434`, healthcheck y volumen persistente) del que depende el servicio `api`, para no instalar/correr Ollama en el host. |
| Git hook de pre-commit | `.githooks/pre-commit` (habilitado con `make install-hooks`): corre `ruff check` y `ruff format --check` dentro de Docker antes de cada commit. |
| Lint y tipado | `ruff` (reglas `E`, `F`, `I`, `UP`, formato con comillas dobles) y `mypy` en modo `strict`, configurados en `pyproject.toml` con targets en el `Makefile` (`make lint`, `make format-check`, `make typecheck`). |

## Cambios para correr modelos en local

- **Modulo 02 / Reto_01 (Anti-alucinaciones)**: reemplacé `Mistral-7B-Instruct-v0.2` (vía `transformers`, pensado para GPU de Colab) por **Qwen3-4B servido con Ollama**, consumido por HTTP contra el mismo contenedor `ollama` de `docker-compose.yml`.
- **Modulo 01 / Proyecto_01**: agente ya diseñado sobre Ollama; sumé el contenedor `ollama` para correrlo sin instalar nada en el host, más `Proyecto_01.ipynb` (documenta el flujo con fallback a mocks si Ollama no está activo) y `app.py` (interfaz Streamlit).
- **Modulo 02 / Reto_02 y Reto_3**: siguen usando Gemini (sin cambio de modelo); solo se ajustó la lectura de la API key para que venga de un `.env` local en vez del gestor de secretos de Colab.

## Proyectos

- [Proyecto 1](https://github.com/cuauhtemocbe/agentic-evals/blob/main/docs/Modulo%2001/Proyecto_01.ipynb)
