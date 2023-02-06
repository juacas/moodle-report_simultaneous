# Simultaneous usage Report #

Este informe intenta ofrecer a los profesores una herramienta sencilla para detectar posibles trampas de los estudiantes durante pruebas de evaluación online.

Los resultados mostrados son meramente orientativos y no son una prueba concluyente de actividad ilícita.

Este informe únicamente analiza los registros de actividad de Moodle para contar el número de diversos eventos de actividad de los usuarios durante un intervalo de tiempo.

El profesor puede analizar a todos los usuarios matriculados o seleccionar una serie de actividades de referencia para extraer de ellas la lista de usuarios a analizar.

El informe realiza las siguientes búsquedas:
- Visitas del usuario en cualquier parte del curso. Si se han seleccionado, se excluyen las actividades de referencia.
- Visitas del usuario en cualquier parte del servidor completo (incluye otros cursos).
- Mensajes instantáneos enviados por cada usuario.
- Acciones realizadas sobre mensajes por cada usuario. Por ejemplo: borrar, leer, escribir mensajes instantáneos.
- Mensajes enviados a conversaciones con otros usuarios analizados.
- IPs diferentes usadas por el usuario.

El plugin no da detalles de las acciones detectadas, únicamente ofrece el número de eventos detectados.

Si un profesor sospecha de actividades inadecuadas, puede hacer un análisis más detallado de los logs de su curso o pedir ayuda a los administradores para una investigación más profunda.

# Contributing #

Cualquier sugerencia de nuevos indicadores será muy bienvenida.

# License #

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
