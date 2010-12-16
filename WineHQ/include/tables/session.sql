DROP TABLE IF EXISTS session_list;
DROP TABLE IF EXISTS session_msg;

CREATE TABLE IF NOT EXISTS session_list (
	session_id	varchar(64) not null,
	data		text,
	stamp		timestamp,
	primary key(session_id)
);

CREATE TABLE IF NOT EXISTS session_msg (
	session_id	varchar(64) not null,
	message		text,
	stamp		timestamp
);
