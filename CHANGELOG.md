# Changelog

All notable changes to the Simultaneous Activities report plugin are documented in this file.

## [1.2.0] - 2026-03-24

### Added
- **Detailed events list table redesign**: Transformed the admin detail listing (`adminlist.php`) from manual HTML table to Moodle's `flexible_table` class for consistent styling and better UX.
- **Text filtering on detail list**: Users can now filter events in the detailed listing by typing a search text that searches across all columns, with a clear filter button to reset.
- **Export to multiple formats**: Detailed event listings can now be downloaded as CSV, Excel (XLSX), ODS, and other formats supported by Moodle's dataformat API. Downloads preserve applied filters.
- **Language strings**: Added Spanish and English translations for new UI elements:
  - Admin list page title (`adminlisttitle`)
  - Filter field label and button (`filtertext`, `filterresults`, `clearfilterresults`)

### Fixed
- **Session mutation warning**: Fixed timing of table initialization in `report_simultaneous_create_table()` to call `setup()` before `is_downloading()`, preventing session mutation warnings when exporting data via `flexible_table`.
- **Download flow reliability**: Implemented direct `core\dataformat::download_data()` path for exports to ensure actual data files (CSV/XLSX) are generated instead of HTML fallback.

### Changed
- **Table layout**: Detail listing now uses Moodle's standardized `flexible_table` rendering with sortable headers and consistent styling.
- **Session management**: Repositioned `write_close()` in `index.php` to occur after table definition to allow all session writes (forms, caches, table prefs) before closing.

### Technical Details
- Updated dependencies: Now requires `core\dataformat` utilities available in Moodle 3.9+.
- Session lifecycle: Session is no longer released until after flexible table preferences are written, preventing false mutation alerts.
- Filter implementation: Case-insensitive text search across all cell values in the detail table, preserving row order.

## [1.0.9] - 2023-XX-XX

Initial stable release with core simultaneous activity detection features:
- Main report view with multiple indicators (V1-V6)
- Module selection and time range filtering
- Bulk messaging action support
- Support for multiple log readers (legacy and internal)
- Multilingual support (Spanish/English)
