queryตรวจสอบคนที่ไม่มี lat,lng
-- รายชื่อลูกค้าที่ "ไม่มีพิกัด GPS เลยสักงานเดียว" (ไม่มีทั้ง start_lat/start_lng
-- และ close_lat/close_lng ในทุกงานที่ไม่ถูกยกเลิก) จึงต้องใช้ lat/lng ของสาขาแทน
-- ตอนแสดงบนแผนที่ /map และ /customer_map
-- (logic เดียวกับ CustomerMap::api_markers() เป๊ะ ๆ: 1 ลูกค้า = 1 หมุด, งานล่าสุด (id สูงสุด) เป็นตัวแทน)

SELECT
    sj.customer_name,
    sj.id            AS job_id,
    sj.bill_no,
    sj.status,
    sj.install_date,
    sj.branch_id,
    br.name          AS branch_name,
    br.lat           AS branch_lat,
    br.lng           AS branch_lng
FROM service_jobs sj
INNER JOIN branches br
        ON br.id = sj.branch_id
       AND br.lat IS NOT NULL
       AND br.lng IS NOT NULL
WHERE sj.status NOT IN ('ยกเลิกนัด')
  AND sj.id = (
        -- ต้องเป็นงานล่าสุด (id สูงสุด) ของลูกค้าคนนี้ในกลุ่มที่มีสาขาพิกัดครบ
        SELECT MAX(sj3.id)
        FROM service_jobs sj3
        INNER JOIN branches brb ON brb.id = sj3.branch_id
                                AND brb.lat IS NOT NULL AND brb.lng IS NOT NULL
        WHERE sj3.customer_name = sj.customer_name
          AND sj3.status NOT IN ('ยกเลิกนัด')
      )
  AND sj.customer_name NOT IN (
        -- ตัดลูกค้าที่มีงานไหนก็ได้ที่มีพิกัด GPS จริง ออกไป (คนกลุ่มนั้นขึ้นแผนที่ด้วย GPS อยู่แล้ว)
        SELECT sj2.customer_name
        FROM service_jobs sj2
        WHERE ((sj2.close_lat IS NOT NULL AND sj2.close_lat != '')
            OR (sj2.start_lat IS NOT NULL AND sj2.start_lat != ''))
          AND sj2.status NOT IN ('ยกเลิกนัด')
      )
ORDER BY sj.customer_name;