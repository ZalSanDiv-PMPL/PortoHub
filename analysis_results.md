# PortoHub Project Analysis

This document provides a comprehensive analysis of the PortoHub project based on the documentation (`.md` files) and the current state of the codebase.

## 1. Project Context & Vision

Based on `DOCS.md` and `ELISITASI.md`, **PortoHub** is a platform designed to document, validate, and showcase the final projects of RPL (Rekayasa Perangkat Lunak) students.

*   **Stakeholders & Roles**: 
    *   **Students**: Upload projects, link to GitHub, submit documentation (videos, PDFs), and receive feedback.
    *   **Teachers**: Validate projects (white-box testing), provide ratings/comments, and track student progress.
    *   **Industry/Public**: View approved projects as a verified portfolio for recruitment.
*   **Core Philosophy (ELISITASI.md)**: The platform acknowledges that students may use AI or cloned repositories, but requires them to deeply understand their code (ATM - Amati, Tiru, Modifikasi). Validation is heavily reliant on GitHub commit history and white-box demonstrations.

## 2. Roadmap Progress (PLANS.md vs Code)

According to `PLANS.md`, the project is divided into several phases. Here is how the actual codebase aligns with these plans:

### Phase 1: Foundation (Stabilkan Fondasi) - 🟡 Mostly Complete
*   **Auth & Security**: The codebase has implemented local auth and GitHub OAuth. `routes/web.php` contains the routes for GitHub linking/unlinking (`GitHubAppAuthController`). Models for `GithubToken` and `GithubMetadata` are present.
*   **Database Foundation**: The foundation is incredibly solid. The 11 tables defined in `DATABASES.md` are **fully mapped** to migrations in `database/migrations` and models in `app/Models/` (e.g., `ClassAssignment`, `Project`, `Validation`, `Student`, `Teacher`).

### Phase 2: UI/UX (Selesaikan UI Utama) - 🟡 In Progress
*   The `resources/views` folder contains basic scaffolding (`dashboard.blade.php`, `profile.blade.php`, `gallery.blade.php`).
*   Livewire/Volt components are starting to be structured inside `resources/views/livewire` (with subdirectories for `dashboard`, `profile`, `pages`).
*   However, the detailed, role-specific views (e.g., Teacher Dashboard vs. Student Dashboard) have not yet been fully built out.

### Phase 3: Product Features (Lengkapi Fitur Produk) - 🔴 Not Started / Pending
*   While the database is ready, the routing and controllers for core features are missing. `routes/web.php` only handles `auth`, `dashboard`, `profile`, and `gallery`.
*   Features waiting to be implemented:
    *   Project Submission workflow (Draft -> Submitted -> Approved).
    *   Teacher Validation and Commenting interface.
    *   GitHub metadata syncing (commit counts, languages).
    *   Public Archive/Portfolio view.

## 3. Database Architecture (DATABASES.md)

The architecture is well-designed for a multi-tenant role-based application:
*   `users` is the base authentication table.
*   `students` and `teachers` map 1-to-1 to users and hold specific domain data (NIS, NIP).
*   `class_assignments` acts as the critical permission pivot. Teachers can only validate students assigned to their classes.
*   `projects` stores the core submission, linked to `project_urls` (live demo, video) and `documentation` (files).
*   `validations` and `comments` hold the teacher's grading and feedback.

## 4. Key Takeaways & Recommended Next Steps

1.  **Routing & Controllers**: The next immediate step is to build out the Livewire components and routing for the core entities. Create routes for `/projects`, `/projects/{id}`, and `/validations`.
2.  **Role-Based Dashboards**: `dashboard.blade.php` currently just points to a generic dashboard. You need to split this logically so that if `auth()->user()->role === 'teacher'`, they see the grading interface, while students see their project submission status.
3.  **GitHub Automation**: `PLANS.md` notes that GitHub token refresh automation is needed. You should set up a Scheduled Command (`app/Console/Commands`) to periodically sync `GithubMetadata` for submitted projects.
4.  **UI Consistency**: As noted in student interviews (`ELISITASI.md`), students struggle with designing their own portfolios. PortoHub must provide a highly polished, visually stunning public gallery view (the `gallery` route) out of the box to solve this pain point.
