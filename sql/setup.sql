#
# create the database
#
CREATE DATABASE `webreplay` /*!40100 DEFAULT CHARACTER SET utf8 */;
use `webreplay`;


#
# the streams
#
CREATE  TABLE `streams` (
  `id` varchar(255) NOT NULL COMMENT 'The ID of the stream' ,
  `description` TEXT NOT NULL DEFAULT '' COMMENT 'A description of the stream' ,
  `position` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'The current position in the stream (i.e. the ID of the entry sent last)' ,
  PRIMARY KEY (`id`))
ENGINE = InnoDb
COMMENT = 'Meta-data for streams';


#
# the stream entries
#
CREATE  TABLE `entries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'The entry ID' ,
  `stream_id` varchar(255) NOT NULL COMMENT 'The ID of the stream that this entry belongs to' ,
  `content` TEXT NOT NULL COMMENT 'The body of the request' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_entries_to_streams` (`stream_id` ASC) ,
  CONSTRAINT `fk_stream_1`
    FOREIGN KEY (`stream_id`)
    REFERENCES `streams` (`id`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
COMMENT = 'The entries of the streams';



#
# the user
#
create user 'webreplay'@'localhost' identified by '123456';
grant delete,insert,update,select,drop on webreplay.* to 'webreplay'@'localhost';
flush privileges;