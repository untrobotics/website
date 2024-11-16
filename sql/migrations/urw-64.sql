ALTER TABLE ftpusers
ADD UNIQUE (name);

ALTER TABLE ftpusers
MODIFY name char(128) NOT NULL;

ALTER TABLE ftpusers
MODIFY passwd char(128) NOT NULL;

CREATE TABLE ftpinvites (id int primary key auto_increment, email varchar(255), registration_token varchar(16));
