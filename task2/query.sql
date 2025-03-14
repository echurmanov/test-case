select u.* from db_users as u
inner join
(
    select user_id
    from db_orders
    group by user_id
    having count(CASE WHEN payed = 0 THEN 1 ELSE NULL END) = 0
        OR count(CASE WHEN payed = 1 THEN 1 ELSE NULL END) / count(CASE WHEN payed = 0 THEN 1 ELSE NULL END) >= 2
) as o on (u.id = o.user_id)
inner join (
    select
        o.user_id
    from db_orders o
        left join db_payments p on (p.order_id = o.id)
    group by o.user_id
    having (
        (count(CASE WHEN status='success' THEN 1 ELSE NULL END) > 0 AND count(CASE WHEN status='fail' THEN 1 ELSE NULL END) = 0)
        OR count(CASE WHEN status='fail' THEN 1 ELSE NULL END) / count(status) < 0.15
    )
) as p on (u.id = p.user_id)