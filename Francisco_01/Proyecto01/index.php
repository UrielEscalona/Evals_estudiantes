<?php

/*
|--------------------------------------------------------------------------
| CONFIGURACIÓN
|--------------------------------------------------------------------------
*/

// Directorio raíz donde se encuentra este archivo index.php (/var/www/html)
$baseDir = __DIR__;

// Directorio del proyecto Python
$workingDirectory = $baseDir . "/Proyecto_01";

// ÚNICA carpeta utilizada para mostrar y procesar imágenes
$imagesPath = $workingDirectory . "/data/images";

// Ruta web relativa de esa misma carpeta para la interfaz HTML
$imagesUrl = "Proyecto_01/data/images";

// Programa Python principal
$mainWebPath = $baseDir . "/mainweb.py";

// Python del entorno virtual
$pythonPath = $baseDir . "/venv/bin/python";


/*
|--------------------------------------------------------------------------
| OBTENER IMÁGENES
|--------------------------------------------------------------------------
*/

$images = [];


if (
    is_dir(
        $imagesPath
    )
) {

    $files =
        scandir(
            $imagesPath
        );


    foreach (
        $files
        as $file
    ) {

        if (
            $file === "." ||
            $file === ".."
        ) {

            continue;

        }


        /*
        |--------------------------------------------------------------------------
        | EVITAR DIRECTORIOS
        |--------------------------------------------------------------------------
        */

        $fullPath =
            $imagesPath .
            "/" .
            $file;


        if (
            !is_file(
                $fullPath
            )
        ) {

            continue;

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDAR EXTENSIÓN
        |--------------------------------------------------------------------------
        */

        $extension =
            strtolower(
                pathinfo(
                    $file,
                    PATHINFO_EXTENSION
                )
            );


        if (
            in_array(
                $extension,
                [
                    "jpg",
                    "jpeg",
                    "png",
                    "gif",
                    "webp"
                ],
                true
            )
        ) {

            $images[] =
                $file;

        }

    }

}


sort(
    $images
);


/*
|--------------------------------------------------------------------------
| VARIABLES DE RESULTADO
|--------------------------------------------------------------------------
*/

$resultado = null;

$imagenProcesada = null;


/*
|--------------------------------------------------------------------------
| PROCESAMIENTO
|--------------------------------------------------------------------------
*/

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset(
        $_POST["procesar"]
    )
) {

    /*
    |--------------------------------------------------------------------------
    | OBTENER IMAGEN SELECCIONADA
    |--------------------------------------------------------------------------
    */

    $imagenSeleccionada =
        $_POST["imagen"]
        ?? "";


    if (
        $imagenSeleccionada !== ""
    ) {

        /*
        |--------------------------------------------------------------------------
        | CONSERVAR SOLO EL NOMBRE
        |--------------------------------------------------------------------------
        */

        $imagenSeleccionada =
            basename(
                $imagenSeleccionada
            );


        /*
        |--------------------------------------------------------------------------
        | RUTA COMPLETA DE LA IMAGEN
        |--------------------------------------------------------------------------
        */

        $rutaProcesamiento =
            $imagesPath .
            "/" .
            $imagenSeleccionada;


        /*
        |--------------------------------------------------------------------------
        | IMAGEN PROCESADA
        |--------------------------------------------------------------------------
        */

        $imagenProcesada =
            $imagenSeleccionada;


        /*
        |--------------------------------------------------------------------------
        | VALIDAR IMAGEN
        |--------------------------------------------------------------------------
        */

        if (
            !is_file(
                $rutaProcesamiento
            )
        ) {

            $resultado = [

                "success" => false,

                "error" =>
                    "La imagen seleccionada no existe.",

                "output" =>
                    "Ruta buscada: " .
                    $rutaProcesamiento

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDAR PYTHON
        |--------------------------------------------------------------------------
        */

        elseif (
            !is_file(
                $pythonPath
            )
        ) {

            $resultado = [

                "success" => false,

                "error" =>
                    "No se encontró el ejecutable de Python.",

                "output" =>
                    "Ruta configurada: " .
                    $pythonPath

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | VALIDAR MAINWEB.PY
        |--------------------------------------------------------------------------
        */

        elseif (
            !is_file(
                $mainWebPath
            )
        ) {

            $resultado = [

                "success" => false,

                "error" =>
                    "No se encontró mainweb.py.",

                "output" =>
                    "Ruta configurada: " .
                    $mainWebPath

            ];

        }


        /*
        |--------------------------------------------------------------------------
        | EJECUTAR PYTHON
        |--------------------------------------------------------------------------
        */

        else {

            $comando =
                "cd " .
                escapeshellarg(
                    $workingDirectory
                ) .
                " && " .
                escapeshellarg(
                    $pythonPath
                ) .
                " " .
                escapeshellarg(
                    $mainWebPath
                ) .
                " " .
                escapeshellarg(
                    $imagenSeleccionada
                ) .
                " " .
                escapeshellarg(
                    $rutaProcesamiento
                );


            /*
            |--------------------------------------------------------------------------
            | EJECUTAR COMANDO
            |--------------------------------------------------------------------------
            */

            $salida =
                shell_exec(
                    $comando .
                    " 2>&1"
                );


            /*
            |--------------------------------------------------------------------------
            | INTENTAR DECODIFICAR JSON DIRECTAMENTE
            |--------------------------------------------------------------------------
            */

            $resultado =
                json_decode(
                    trim(
                        $salida
                        ?? ""
                    ),
                    true
                );


            /*
            |--------------------------------------------------------------------------
            | RECUPERAR EL ÚLTIMO JSON VÁLIDO
            |--------------------------------------------------------------------------
            */

            if (
                !is_array(
                    $resultado
                )
            ) {

                $lineas =
                    preg_split(
                        "/\r\n|\n|\r/",
                        trim(
                            $salida
                            ?? ""
                        )
                    );


                $resultado =
                    null;


                /*
                |--------------------------------------------------------------
                | BUSCAR DESDE EL FINAL
                |--------------------------------------------------------------
                */

                for (
                    $i =
                        count(
                            $lineas
                        ) - 1;

                    $i >= 0;

                    $i--
                ) {

                    $linea =
                        trim(
                            $lineas[$i]
                        );


                    if (
                        $linea === ""
                    ) {

                        continue;

                    }


                    /*
                    |----------------------------------------------------------
                    | INTENTAR DECODIFICAR LA LÍNEA COMPLETA
                    |----------------------------------------------------------
                    */

                    $datos =
                        json_decode(
                            $linea,
                            true
                        );


                    if (
                        is_array(
                            $datos
                        )
                    ) {

                        $resultado =
                            $datos;

                        break;

                    }


                    /*
                    |----------------------------------------------------------
                    | BUSCAR { DENTRO DE LA LÍNEA
                    |----------------------------------------------------------
                    */

                    $posicion =
                        strpos(
                            $linea,
                            "{"
                        );


                    if (
                        $posicion !== false
                    ) {

                        $posibleJson =
                            substr(
                                $linea,
                                $posicion
                            );


                        $datos =
                            json_decode(
                                $posibleJson,
                                true
                            );


                        if (
                            is_array(
                                $datos
                            )
                        ) {

                            $resultado =
                                $datos;

                            break;

                        }

                    }

                }

            }


            /*
            |--------------------------------------------------------------------------
            | SI NO SE OBTUVO JSON
            |--------------------------------------------------------------------------
            */

            if (
                !is_array(
                    $resultado
                )
            ) {

                $resultado = [

                    "success" => false,

                    "error" =>
                        "mainweb.py no devolvió una respuesta JSON válida.",

                    "output" =>
                        trim(
                            $salida
                            ?? "No se recibió ninguna salida."
                        )

                ];

            }

        }

    }

    else {

        $resultado = [

            "success" => false,

            "error" =>
                "No se seleccionó ninguna imagen."

        ];

    }

}

?>


<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Analizador de imágenes
    </title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


<div class="container py-5">


<?php if (
    $resultado === null
): ?>


    <!--
    ========================================================================
    PANTALLA PRINCIPAL
    ========================================================================
    -->

    <div class="card shadow">

        <div class="card-body">


            <h1 class="text-center mb-4">

                Analizador de imágenes

            </h1>


            <?php if (
                empty(
                    $images
                )
            ): ?>


                <div class="alert alert-warning text-center">

                    No se encontraron imágenes en:

                    <br>

                    <code>

                        /var/www/html/Proyecto_01/data/images

                    </code>

                </div>


            <?php else: ?>


                <!--
                =================================================================
                CARRUSEL
                =================================================================
                -->

                <div
                    id="imageCarousel"
                    class="carousel slide"
                    data-bs-ride="false"
                >

                    <div class="carousel-inner">


                        <?php foreach (
                            $images
                            as $index => $image
                        ): ?>


                            <div
                                class="carousel-item <?php

                                echo
                                    $index === 0
                                    ? "active"
                                    : "";

                                ?>"
                                data-image="<?php

                                echo htmlspecialchars(
                                    $image,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>"
                            >

                                <div class="text-center">

                                    <img
                                        src="<?php

                                        echo
                                            $imagesUrl;

                                        ?>/<?php

                                        echo
                                            rawurlencode(
                                                $image
                                            );

                                        ?>"
                                        class="img-fluid rounded shadow-sm"
                                        style="max-height: 500px;"
                                        alt="<?php

                                        echo htmlspecialchars(
                                            $image,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                        ?>"
                                    >


                                    <h5 class="mt-3">

                                        <?php

                                        echo htmlspecialchars(
                                            $image,
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                        ?>

                                    </h5>

                                </div>

                            </div>


                        <?php endforeach; ?>


                    </div>


                    <!-- ANTERIOR -->

                    <button
                        class="carousel-control-prev"
                        type="button"
                        data-bs-target="#imageCarousel"
                        data-bs-slide="prev"
                    >

                        <span
                            class="carousel-control-prev-icon bg-dark rounded-circle"
                        >
                        </span>

                        <span class="visually-hidden">

                            Anterior

                        </span>

                    </button>


                    <!-- SIGUIENTE -->

                    <button
                        class="carousel-control-next"
                        type="button"
                        data-bs-target="#imageCarousel"
                        data-bs-slide="next"
                    >

                        <span
                            class="carousel-control-next-icon bg-dark rounded-circle"
                        >
                        </span>

                        <span class="visually-hidden">

                            Siguiente

                        </span>

                    </button>


                </div>


                <!--
                =================================================================
                FORMULARIO
                =================================================================
                -->

                <form
                    method="post"
                    class="text-center mt-4"
                >

                    <input
                        type="hidden"
                        name="imagen"
                        id="imagenSeleccionada"
                        value="<?php

                        echo htmlspecialchars(
                            $images[0],
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>"
                    >


                    <button
                        type="submit"
                        name="procesar"
                        class="btn btn-primary btn-lg px-5"
                    >

                        Procesar

                    </button>

                </form>


                <!-- CONTADOR -->

                <div class="text-center mt-3">

                    <span class="badge bg-secondary">

                        <?php

                        echo
                            count(
                                $images
                            );

                        ?>

                        imágenes disponibles

                    </span>

                </div>


            <?php endif; ?>


        </div>

    </div>


<?php else: ?>


    <!--
    ========================================================================
    PANTALLA DE RESULTADO
    ========================================================================
    -->

    <div class="card shadow">

        <div class="card-body">


            <h1 class="text-center mb-4">

                Resultado del análisis

            </h1>


            <!--
            ===================================================================
            IMAGEN PROCESADA
            ===================================================================
            -->

            <?php if (
                $imagenProcesada !== null
            ): ?>


                <div class="text-center mb-4">

                    <img
                        src="<?php

                        echo
                            $imagesUrl;

                        ?>/<?php

                        echo
                            rawurlencode(
                                $imagenProcesada
                            );

                        ?>"
                        class="img-fluid rounded shadow-sm"
                        style="max-height: 400px;"
                        alt="Imagen procesada"
                    >


                    <h5 class="mt-3">

                        <?php

                        echo htmlspecialchars(
                            $imagenProcesada,
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>

                    </h5>

                </div>


            <?php endif; ?>


            <?php if (
                isset(
                    $resultado["success"]
                ) &&
                $resultado["success"] === true
            ): ?>


                <!-- ÉXITO -->

                <div class="alert alert-success">

                    <h4 class="alert-heading">

                        Análisis completado

                    </h4>

                    <p class="mb-0">

                        La imagen fue procesada correctamente.

                    </p>

                </div>


                <!-- RESULTADOS -->

                <div class="card mb-3">

                    <div class="card-body">


                        <!-- CATEGORÍA -->

                        <h5>

                            Categoría

                        </h5>

                        <div class="mb-4">

                            <span class="badge bg-primary fs-5">

                                <?php

                                echo htmlspecialchars(
                                    $resultado["category"]
                                    ?? "N/D",
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>

                            </span>

                        </div>


                        <!-- CONFIANZA -->

                        <h5>

                            Confianza

                        </h5>

                        <div class="mb-4">

                            <?php

                            if (
                                isset(
                                    $resultado["confidence"]
                                )
                            ) {

                                $confidence =
                                    (float)
                                    $resultado["confidence"];


                                $confidencePercent =
                                    max(
                                        0,
                                        min(
                                            100,
                                            $confidence * 100
                                        )
                                    );

                            ?>

                                <div
                                    class="progress"
                                    style="height: 30px;"
                                >

                                    <div
                                        class="progress-bar"
                                        role="progressbar"
                                        style="width: <?php

                                        echo
                                            $confidencePercent;

                                        ?>%;"
                                    >

                                        <?php

                                        echo number_format(
                                            $confidencePercent,
                                            1
                                        );

                                        ?>%

                                    </div>

                                </div>


                            <?php

                            }

                            else {

                                echo "N/D";

                            }

                            ?>

                        </div>


                        <!-- DESCRIPCIÓN -->

                        <h5>

                            Descripción

                        </h5>

                        <div
                            class="border rounded p-3 bg-light mb-4"
                        >

                            <?php

                            if (
                                !empty(
                                    $resultado[
                                        "description_html"
                                    ]
                                )
                            ) {

                                echo
                                    $resultado[
                                        "description_html"
                                    ];

                            }

                            elseif (
                                isset(
                                    $resultado[
                                        "description"
                                    ]
                                )
                            ) {

                                echo nl2br(
                                    htmlspecialchars(
                                        $resultado[
                                            "description"
                                        ],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    )
                                );

                            }

                            else {

                                echo "N/D";

                            }

                            ?>

                        </div>


                        <!-- LATENCIA -->

                        <?php if (
                            isset(
                                $resultado[
                                    "latency_sec"
                                ]
                            )
                        ): ?>


                            <h5>

                                Latencia

                            </h5>

                            <p>

                                <span
                                    class="badge bg-secondary fs-6"
                                >

                                    <?php

                                    echo number_format(
                                        (float)
                                        $resultado[
                                            "latency_sec"
                                        ],
                                        2
                                    );

                                    ?>

                                    segundos

                                </span>

                            </p>


                        <?php endif; ?>


                    </div>

                </div>


                <!-- INFORMACIÓN TÉCNICA -->

                <div
                    class="accordion mb-3"
                    id="technicalInfo"
                >

                    <div class="accordion-item">

                        <h2
                            class="accordion-header"
                            id="headingTechnical"
                        >

                            <button
                                class="accordion-button collapsed"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#collapseTechnical"
                            >

                                Información técnica

                            </button>

                        </h2>


                        <div
                            id="collapseTechnical"
                            class="accordion-collapse collapse"
                        >

                            <div class="accordion-body">

                                <p>

                                    <strong>

                                        Imagen:

                                    </strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $resultado["image"]
                                        ?? $imagenProcesada
                                        ?? "N/D",
                                        ENT_QUOTES,
                                        "UTF-8"
                                    );

                                    ?>

                                </p>


                                <p class="mb-0">

                                    <strong>

                                        Ruta utilizada:

                                    </strong>

                                    <br>

                                    <code>

                                        <?php

                                        echo htmlspecialchars(
                                            $resultado["path"]
                                            ?? "N/D",
                                            ENT_QUOTES,
                                            "UTF-8"
                                        );

                                        ?>

                                    </code>

                                </p>

                            </div>

                        </div>

                    </div>

                </div>


            <?php else: ?>


                <!-- ERROR -->

                <div class="alert alert-danger">

                    <h4>

                        Error durante el procesamiento

                    </h4>


                    <p>

                        <?php

                        echo nl2br(
                            htmlspecialchars(
                                $resultado["error"]
                                ?? "Error desconocido.",
                                ENT_QUOTES,
                                "UTF-8"
                            )
                        );

                        ?>

                    </p>


                    <?php if (
                        isset(
                            $resultado["output"]
                        ) &&
                        $resultado["output"] !== ""
                    ): ?>


                        <hr>

                        <details open>

                            <summary>

                                Mostrar salida técnica

                            </summary>

                            <pre
                                class="mt-3 mb-0"
                                style="white-space: pre-wrap;"
                            ><?php

                            echo htmlspecialchars(
                                $resultado["output"],
                                ENT_QUOTES,
                                "UTF-8"
                            );

                            ?></pre>

                        </details>


                    <?php endif; ?>


                </div>


            <?php endif; ?>


            <!-- VOLVER -->

            <div class="text-center mt-4">

                <a
                    href="index.php"
                    class="btn btn-secondary btn-lg px-5"
                >

                    Volver a las imágenes

                </a>

            </div>


        </div>

    </div>


<?php endif; ?>


</div>


<!-- Bootstrap -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
>
</script>


<script>

/*
|--------------------------------------------------------------------------
| MANTENER SINCRONIZADA LA IMAGEN SELECCIONADA
|--------------------------------------------------------------------------
*/

const carousel =
    document.getElementById(
        "imageCarousel"
    );


const imagenSeleccionada =
    document.getElementById(
        "imagenSeleccionada"
    );


if (
    carousel &&
    imagenSeleccionada
) {

    carousel.addEventListener(
        "slid.bs.carousel",

        function (event) {

            const imagen =
                event.relatedTarget.dataset.image;


            imagenSeleccionada.value =
                imagen;

        }

    );

}

</script>


</body>

</html>