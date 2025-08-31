-- Jobs tablosuna retry/backoff kolonu ekle
ALTER TABLE jobs
  ADD COLUMN next_attempt_at DATETIME NULL AFTER attempts;
