import os
import tempfile

import requests
import streamlit as st

from agent import DataClassifierAgent

IMAGE_CATEGORIES = ["Factura", "Ticket", "Logo", "Otro"]
TEXT_CATEGORIES = [
    "Queja",
    "Felicitación",
    "Error de Base de Datos",
    "Alerta de Infraestructura",
]


def is_ollama_active() -> bool:
    try:
        response = requests.get("http://localhost:11434", timeout=2)
        return response.status_code == 200
    except requests.exceptions.RequestException:
        return False


@st.cache_resource
def get_agent() -> DataClassifierAgent:
    return DataClassifierAgent()


def render_result(result: dict):
    if result.get("success"):
        col1, col2, col3 = st.columns(3)
        col1.metric("Categoría", result["category"])
        col2.metric("Confianza", f"{result['confidence'] * 100:.1f}%")
        col3.metric("Latencia", f"{result['latency_sec']:.1f} s")
        st.markdown("**Descripción**")
        st.write(result["description"])
    else:
        st.error(result.get("description", "No se pudo obtener una respuesta."))


st.set_page_config(
    page_title="Clasificador de Datos",
    page_icon="🖼️",
    layout="wide",
)

st.title("Clasificador de datos con LLM local")
st.caption("Sube imágenes o pega texto. El agente usa Ollama (`qwen3-vl:4b`) para clasificar y describir.")

agent = get_agent()
ollama_ok = is_ollama_active()

with st.sidebar:
    st.header("Estado")
    if ollama_ok:
        st.success("Ollama está activo")
    else:
        st.error("Ollama no está disponible")
        st.caption("Inicia Ollama y asegúrate de tener el modelo descargado:")
        st.code(f"ollama pull {agent.model}", language="bash")

    st.markdown("---")
    st.markdown(f"**Modelo:** `{agent.model}`")
    st.markdown(f"**Timeout:** {agent.timeout}s")

tab_images, tab_text = st.tabs(["Imágenes", "Texto"])

with tab_images:
    uploaded_files = st.file_uploader(
        "Carga una o varias imágenes",
        type=["png", "jpg", "jpeg", "webp", "bmp", "gif"],
        accept_multiple_files=True,
    )

    if uploaded_files:
        st.subheader("Vista previa")
        preview_cols = st.columns(min(3, len(uploaded_files)))
        for i, uploaded in enumerate(uploaded_files):
            with preview_cols[i % len(preview_cols)]:
                st.image(uploaded, caption=uploaded.name, use_container_width=True)

        analyze = st.button("Analizar imágenes", type="primary", disabled=not ollama_ok)

        if analyze:
            if not ollama_ok:
                st.warning("Ollama no está en ejecución. Inícialo e intenta de nuevo.")
            else:
                for uploaded in uploaded_files:
                    st.markdown("---")
                    st.markdown(f"### {uploaded.name}")
                    col_img, col_res = st.columns([1, 2])
                    with col_img:
                        st.image(uploaded, use_container_width=True)

                    suffix = os.path.splitext(uploaded.name)[1] or ".png"
                    with tempfile.NamedTemporaryFile(delete=False, suffix=suffix) as tmp:
                        tmp.write(uploaded.getvalue())
                        tmp_path = tmp.name

                    try:
                        with col_res:
                            with st.spinner("Analizando con visión multimodal..."):
                                result = agent.analyze_image(
                                    tmp_path,
                                    categories=IMAGE_CATEGORIES,
                                )
                            render_result(result)
                    finally:
                        os.unlink(tmp_path)

with tab_text:
    text = st.text_area("Texto o log a clasificar", height=160)
    classify = st.button("Clasificar texto", type="primary", disabled=not ollama_ok)

    if classify:
        if not text.strip():
            st.warning("Escribe un texto primero.")
        elif not ollama_ok:
            st.warning("Ollama no está en ejecución. Inícialo e intenta de nuevo.")
        else:
            with st.spinner("Clasificando..."):
                result = agent.classify_text(text.strip(), TEXT_CATEGORIES)
            render_result(result)
