# MediQueue (iptProject)

MediQueue is a web-based clinic appointment and queue management system built with PHP and MySQL. It streamlines the full outpatient workflow: patient registration, doctor discovery, slot booking, appointment handling, records, notifications, and reporting.

## System Overview

MediQueue is designed for multi-role healthcare operations with role-based dashboards and APIs:

- Patient portal for booking and managing appointments.
- Doctor portal for schedules, appointments, and patient records.
- Staff/Admin portal for operations, user management, and reports.

The system uses a modular structure:

- `pages/` for web UI screens by role.
- `api/` for JSON endpoints used by the frontend.
- `includes/` for shared configuration, auth helpers, utilities, and security guards.
- `cron/` for background maintenance jobs.

## Core Features

### 1. Authentication and Account Management

- Registration, login, and logout.
- Email verification flow.
- Forgot-password and reset-password flow.
- Change password for logged-in users.
- Role-based access control for `patient`, `doctor`, `staff`, and `admin`.

### 2. Appointment and Queue Management

- Doctor search and availability viewing.
- Time-slot generation and schedule management.
- Slot reservation (temporary hold) during booking.
- Appointment booking with visit type, notes, and reminder preferences.
- Full appointment lifecycle statuses:
	- `pending`, `confirmed`, `in_progress`, `completed`, `cancelled`, `no_show`, `rescheduled`.
- Reschedule and cancel flows.
- Waitlist join/list support for fully booked schedules.

### 3. Role-Based Dashboards

- Patient dashboard:
	- Upcoming appointments, status tracking, history, and feedback submission.
- Doctor dashboard:
	- Daily/upcoming appointments, quick confirm/cancel/complete actions.
	- Medical record creation and updates after consultations.
- Admin/Staff dashboard:
	- Appointment oversight (including bulk actions).
	- User and doctor management tools.

### 4. Profiles and Clinical Data

- Patient profiles with personal and medical context (allergies, history, emergency contact).
- Doctor profiles with specialization, experience, clinic details, and available days.
- Patient records linked to completed appointments (diagnosis, prescription, notes).
- Feedback and rating records tied to appointments.

### 5. Notifications and Communication

- In-app notifications with read/unread state.
- Notification listing, unread counts, and mark-as-read actions.
- Email and SMS-ready notification model support.
- Mailer integration for appointment-related emails.

### 6. Reports and Analytics

- Dashboard KPI stats (users, doctors, patients, appointments).
- Appointment trend and status distribution reports.
- Date-filtered appointment summaries.
- Doctor and patient report endpoints.
- Top-doctor and overview/trend reporting endpoints.

### 7. Security and Reliability

- API guard for method, authentication, and role enforcement.
- CSRF token generation and validation helpers.
- Input validation utilities and centralized response helpers.
- Rate limiting support (`storage/rates/`).
- Audit log reporting.
- Cron job to auto-cancel stale pending appointments and clean expired slot reservations.

## Technology Stack

- Backend: PHP 8+
- Database: MySQL (InnoDB, utf8mb4)
- Frontend: Server-rendered PHP pages with vanilla JavaScript and shared UI utilities
- Mail: PHPMailer

## High-Level Module Map

- `api/auth/` -> authentication and account recovery endpoints.
- `api/appointments/` -> booking, list/get/update, and slot operations.
- `api/doctors/` -> doctor list, profile, schedule, and availability endpoints.
- `api/patients/` -> patient profile endpoints.
- `api/notifications/` -> notification list/count/mark-read/send.
- `api/reports/` -> analytics and reporting endpoints.
- `api/users/` -> admin/staff user management endpoints.
- `api/waitlist/` -> waitlist join/list endpoints.

## Summary

MediQueue provides an end-to-end digital workflow for outpatient appointment operations, from patient self-service booking to doctor consultation management and admin-level reporting.