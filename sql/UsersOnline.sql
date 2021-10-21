CREATE TABLE /*_*/user_online (
 uo_id int NOT NULL AUTO_INCREMENT,
 uo_user_id int NOT NULL DEFAULT '0',
 uo_session_id varbinary(32) NOT NULL,
 uo_ip_address varbinary(100) NOT NULL,
 uo_lastPageTitle varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
 uo_lastLinkURL varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
 uo_start_session datetime DEFAULT NULL,
 uo_end_session datetime DEFAULT NULL,
 uo_prev_end_session datetime DEFAULT NULL,
 PRIMARY KEY (uo_id)
) /*$wgDBTableOptions*/;

CREATE UNIQUE INDEX uo_user ON /*_*/user_online (uo_user_id, uo_ip_address);
