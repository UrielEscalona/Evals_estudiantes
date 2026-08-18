import sys
import json
import html
import logging
from pathlib import Path


# ---------------------------------------------------------
# RUTAS DEL PROYECTO
# ---------------------------------------------------------

BASE_DIR = Path(__file__).resolve().parent

PROJECT_DIR = BASE_DIR / "Proyecto_01"


# ---------------------------------------------------------
# ASEGURAR QUE PYTHON ENCUENTRE LOS MÓDULOS
# ---------------------------------------------------------

if str(PROJECT_DIR) not in sys.path:
    sys.path.insert(0, str(PROJECT_DIR))


# ---------------------------------------------------------
# IMPORTACIONES DEL PROYECTO
# ---------------------------------------------------------

from agent import DataClassifierAgent
from logger_config import logger


# ---------------------------------------------------------
# PREPARAR DESCRIPCIÓN HTML
# ---------------------------------------------------------

def preparar_descripcion_html(descripcion):
    """
    Convierte la descripción del agente en HTML básico
    para su presentación en la interfaz web.
    """

    if not descripcion:
        return "<p>N/D</p>"

    descripcion = str(descripcion).strip()

    # Escapar HTML para evitar interpretar contenido
    # generado por el modelo como código HTML.
    descripcion = html.escape(descripcion)

    # Separar párrafos.
    parrafos = descripcion.split("\n\n")

    html_parrafos = []

    for parrafo in parrafos:

        parrafo = parrafo.replace("\n", "<br>")

        html_parrafos.append(
            f"<p>{parrafo}</p>"
        )

    return "\n".join(html_parrafos)


# ---------------------------------------------------------
# ENVIAR JSON
# ---------------------------------------------------------

def enviar_json(datos):
    """
    Envía exclusivamente JSON por stdout.
    """

    print(
        json.dumps(
            datos,
            ensure_ascii=False
        )
    )


# ---------------------------------------------------------
# PROGRAMA PRINCIPAL
# ---------------------------------------------------------

def main():

    # -----------------------------------------------------
    # VALIDAR ARGUMENTOS
    # -----------------------------------------------------

    if len(sys.argv) != 3:

        enviar_json({
            "success": False,
            "error": (
                "Uso: mainweb.py <nombre_imagen> <ruta_imagen>"
            )
        })

        return 1


    imagen = sys.argv[1]
    ruta_imagen = sys.argv[2]


    # -----------------------------------------------------
    # EJECUTAR ANÁLISIS
    # -----------------------------------------------------

    try:

        agent = DataClassifierAgent()

        resultado = agent.analyze_image(
            ruta_imagen
        )


        # -------------------------------------------------
        # CONSTRUIR RESPUESTA
        # -------------------------------------------------

        if resultado.get("success"):

            respuesta = {
                "success": True,

                "image": imagen,

                "path": ruta_imagen,

                "category": resultado.get(
                    "category",
                    "N/D"
                ),

                "confidence": resultado.get(
                    "confidence",
                    0
                ),

                "description": resultado.get(
                    "description",
                    "N/D"
                ),

                "description_html":
                    preparar_descripcion_html(
                        resultado.get(
                            "description",
                            ""
                        )
                    ),

                "latency_sec": resultado.get(
                    "latency_sec",
                    0
                )
            }

        else:

            respuesta = {
                "success": False,

                "image": imagen,

                "path": ruta_imagen,

                "error": resultado.get(
                    "description",
                    "El análisis de la imagen falló."
                )
            }


        # -------------------------------------------------
        # JSON ÚNICAMENTE EN STDOUT
        # -------------------------------------------------

        enviar_json(respuesta)

        return 0


    except Exception as error:

        enviar_json({
            "success": False,
            "image": imagen,
            "path": ruta_imagen,
            "error": str(error)
        })

        return 1


# ---------------------------------------------------------
# PUNTO DE ENTRADA
# ---------------------------------------------------------

if __name__ == "__main__":
    sys.exit(main())