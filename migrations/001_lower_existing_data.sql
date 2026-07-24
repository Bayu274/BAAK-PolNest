-- Migration: Lowercase existing data in student_advisors
-- Run AFTER deploying Phase 2 code changes (normalize in Advisor model).
-- Idempotent — safe to run multiple times.

UPDATE student_advisors
SET nim         = LOWER(nim),
    student_name = LOWER(student_name),
    advisor_name = LOWER(advisor_name)
WHERE nim         != LOWER(nim)
   OR student_name != LOWER(student_name)
   OR advisor_name != LOWER(advisor_name);
