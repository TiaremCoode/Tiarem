# X La Copa — Maquetado

Maquetado estático (sin funcionalidad de backend) del sitio del Sistema de Gestión Deportiva Modular (SGDM), pensado **Mobile First** con HTML, CSS y JavaScript.

## Cómo verlo
Abrí `index.html` en el navegador, o hacé clic en cualquier `.html` — la navegación entre páginas ya funciona.

## Estructura de carpetas

```
xlacopa/
├── index.html              Página de inicio
├── buscar-torneos.html     Búsqueda / consulta pública de torneos
├── detalle-torneo.html     Detalle: calendario, tabla de posiciones
├── crear-torneo.html       Alta de torneo en 3 pasos
├── perfil.html             Perfil del usuario
├── css/
│   ├── base.css            Tokens de diseño, reset, componentes base
│   ├── nav.css              Navegación (tabbar móvil / navbar desktop)
│   ├── home.css / buscar.css / detalle.css / crear.css / perfil.css
├── js/
│   └── nav.js               Vista compartida de navegación (inyecta topbar/tabbar)
├── assets/
│   └── logo.png              Logo del sitio
└── views/                    Carpeta reservada para cuando se sume el backend PHP (MVC)
```

## Relación con el modelo MVC del proyecto
Este maquetado es la capa de **Vista**. Cada `.html` representa una vista distinta según los mockups pedidos (inicio, búsqueda, detalle, perfil, creación de torneo). `js/nav.js` funciona como un componente de vista reutilizable, para no repetir la navegación en cada archivo. Cuando se integre el backend en PHP, estas vistas pasarán a ubicarse en `views/` y a recibir datos dinámicos desde los controladores — por ahora todo el contenido es estático (datos de ejemplo/dummy).

## Paleta de colores
| Color | Hex | Uso |
|---|---|---|
| Negro carbón | `#1A1A1A` | Fondo principal |
| Azul gris oscuro | `#243447` | Tarjetas, superficies |
| Plata | `#C8CDD3` | Texto destacado, botones primarios, acentos |

## Mobile First
Todo el CSS parte de estilos para pantallas chicas y usa `@media (min-width: ...)` para expandir a tablet (768–900px) y escritorio (900px+). La navegación usa una barra inferior fija (tabbar) en móvil, que se reemplaza por una navbar horizontal en pantallas grandes. El layout usa Flexbox y CSS Grid según corresponda.

## Requerimientos no funcionales aplicados en el maquetado
- Tipografía consistente en todo el sitio (Teko para títulos, Inter para texto).
- Botones grandes, con buen contraste y texto breve.
- Paleta de colores suave, sin tonos vibrantes.
- Estructura lista para mensajes de error claros (`.error-msg` en `base.css`).
- Bloque de orientación para nuevos usuarios en la página de inicio.
