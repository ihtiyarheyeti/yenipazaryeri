-- jobs tablosuna performans indexleri ve 'dead' status'u ekle
USE yenipazaryeri;

-- Status enum'ı güncelle
ALTER TABLE jobs MODIFY status ENUM('pending','running','done','error','dead') DEFAULT 'pending';

-- Performans indexleri ekle
CREATE INDEX IF NOT EXISTS idx_jobs_status_next ON jobs (status, next_attempt_at);
CREATE INDEX IF NOT EXISTS idx_jobs_created ON jobs (created_at);

-- logs tablosu varsa index ekle
CREATE INDEX IF NOT EXISTS idx_logs_type_time ON logs (type, created_at);

-- Mevcut verileri kontrol et
SELECT status, COUNT(*) as count FROM jobs GROUP BY status;
