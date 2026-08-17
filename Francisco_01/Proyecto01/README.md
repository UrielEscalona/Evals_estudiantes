# Guía de Instalación y Despliegue (Debian) - Francisco_01

### Requisitos del Sistema:
```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php python3 python3-pip python3-venv -y
```

### Descomprimir los Archivos del Proyecto:
Asegúrate de colocar los archivos en la raíz web para que la estructura quede de la siguiente manera:
```text
/var/www/html/
├── agent.log
├── index.php
├── mainweb.py
└── Proyecto_01/
    ├── agent.py
    ├── logger_config.py
    └── data/
        └── images/
```

### Crear el Entorno Virtual e Instalar Dependencias:
```bash
cd /var/www/html
python3 -m venv venv
./venv/bin/pip install -r Proyecto_01/requirements.txt
```

### Crear el Archivo de Logs (agent.log):
```bash
sudo touch /var/www/html/agent.log
```

### Configurar Permisos del Sistema:
```bash
# 1. Asignar el propietario correcto (el usuario de Apache) a todo el proyecto
sudo chown -R www-data:www-data /var/www/html/

# 2. Configurar permisos estándar para carpetas (755) y archivos (644)
sudo find /var/www/html/ -type d -exec chmod 755 {} \;
sudo find /var/www/html/ -type f -exec chmod 644 {} \;

# 3. Dar permisos específicos de escritura al archivo de log
sudo chmod 664 /var/www/html/agent.log

# 4. Asegurar que los ejecutables tengan permisos de ejecución
sudo chmod +x /var/www/html/mainweb.py
sudo chmod +x /var/www/html/venv/bin/python
```

### 5. Validar el Entorno desde la Terminal:
Ejecuta el siguiente comando para verificar la comunicación entre PHP y el entorno virtual de Python:
```bash
/var/www/html/venv/bin/python /var/www/html/mainweb.py
```

