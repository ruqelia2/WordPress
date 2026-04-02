# WP DataCharts Pro

> 强大的 WordPress 图表可视化插件，支持 20+ 种图表类型、多数据源、多渲染引擎。

---

## ✨ Features

- **20+ Chart Types** — line, bar, pie, doughnut, area, scatter, radar, polar, bubble, heatmap, funnel, gauge, candlestick, treemap, waterfall, sankey, boxplot, timeline, mixed, map
- **Multiple Rendering Engines** — Chart.js, Highcharts, ApexCharts, ECharts
- **Multiple Data Sources** — MySQL, WordPress DB ($wpdb), CSV, Excel, Google Sheets, REST API, JSON, XML
- **Shortcode & Gutenberg Block** — `[wpdcp_chart id="1"]` and the `wpdcp/chart` block
- **Two-Layer Caching** — Object cache (L1) + transients (L2)
- **Built-in Color Palettes** — default, pastel, vibrant, monochrome, business
- **Role-Based Capabilities** — fine-grained access control for administrators and editors
- **Analytics** — optional chart view and interaction tracking

---

## 📋 Requirements

| Requirement | Minimum Version |
|---|---|
| PHP | 8.1 |
| WordPress | 6.4 |

---

## 🚀 Installation

### From Source

```bash
# Clone or download the repository
git clone https://github.com/ruqelia2/WordPress.git

# Navigate to the plugin directory
cd WordPress/wp-datacharts-pro

# Install PHP dependencies (dev)
composer install

# Activate the plugin via WP-CLI or the WordPress admin
wp plugin activate wp-datacharts-pro
```

---

## 📁 Directory Structure

```
wp-datacharts-pro/
├── wp-datacharts-pro.php        # Plugin entry point
├── uninstall.php                # Clean-up on plugin deletion
├── composer.json                # PHP dependency management
├── README.md
│
├── includes/                    # PHP source (PSR-4 namespace: WPDCP\)
│   ├── Core/
│   │   ├── Plugin.php           # Singleton bootstrap
│   │   ├── Container.php        # Dependency injection container
│   │   ├── Activator.php        # Activation hooks
│   │   ├── Deactivator.php      # Deactivation hooks
│   │   ├── I18n.php             # Internationalisation loader
│   │   └── Assets.php           # CSS/JS enqueueing
│   ├── Database/
│   │   ├── Schema.php           # Table creation / migration
│   │   ├── Migrator.php         # Version-based migration runner
│   │   ├── ChartRepository.php  # CRUD for charts
│   │   └── DataSourceRepository.php
│   ├── Security/
│   │   ├── Sanitizer.php        # Input sanitization helpers
│   │   ├── Validator.php        # Input validation helpers
│   │   └── CapabilityManager.php
│   ├── Cache/
│   │   ├── CacheManager.php     # Two-layer cache (object + transient)
│   │   └── CacheStrategy.php    # Interface for cache drivers
│   ├── Utils/
│   │   ├── Logger.php           # WP_DEBUG-gated logger
│   │   ├── Helpers.php          # Utility helpers
│   │   └── ColorPalette.php     # Built-in chart color palettes
│   ├── Admin/
│   │   ├── AdminMenu.php        # Admin menu registration
│   │   ├── AdminPages.php       # Page render callbacks
│   │   └── Settings.php         # Plugin settings (WP Settings API)
│   └── Frontend/
│       ├── Shortcodes.php       # [wpdcp_chart] shortcode
│       ├── GutenbergBlock.php   # wpdcp/chart block
│       └── ChartDisplay.php     # HTML output helper
│
├── templates/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── settings.php
│   │   ├── all-charts.php
│   │   ├── add-chart.php
│   │   ├── data-sources.php
│   │   └── templates.php
│   └── frontend/
│       └── chart-container.php
│
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   └── frontend.css
│   ├── js/
│   │   ├── admin/app.js
│   │   └── frontend/chart-renderer.js
│   └── images/
│
├── languages/                   # Translation files (.pot / .po / .mo)
│
└── tests/
    ├── bootstrap.php
    └── Unit/
        ├── SanitizerTest.php
        └── ContainerTest.php
```

---

## 🛠 Development Guide

### PHP Dependencies

```bash
cd wp-datacharts-pro
composer install
```

### Running Tests

```bash
composer test
# or directly:
./vendor/bin/phpunit --bootstrap tests/bootstrap.php tests/
```

### Static Analysis (PHPStan level 8)

```bash
composer phpstan
```

### Code Style (WordPress Coding Standards)

```bash
composer phpcs
```

---

## 🗺 Roadmap

| Phase | Description | Status |
|---|---|---|
| **Phase 1** | Core framework, DB schema, security, caching, admin UI skeleton | ✅ Complete |
| **Phase 2** | Chart editor UI, REST API endpoints, data source connectors | 🔜 Planned |
| **Phase 3** | Chart library integrations (Chart.js, Highcharts, ApexCharts, ECharts) | 🔜 Planned |
| **Phase 4** | Analytics dashboard, CSV/Excel import, Google Sheets connector | 🔜 Planned |
| **Phase 5** | Performance optimisation, accessibility audit, i18n, release | 🔜 Planned |

---

## 📄 License

Licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
