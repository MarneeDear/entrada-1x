INSERT INTO `settings` (`shortname`, `organisation_id`, `value`)
  VALUES
	('valid_mimetypes', NULL, '{\"default\":[\"image\\/jpeg\",\"image\\/gif\",\"image\\/png\",\"text\\/csv\",\"text\\/richtext\",\"application\\/rtf\",\"application\\/pdf\",\"application\\/zip\",\"application\\/msword\",\"application\\/vnd.ms-office\",\"application\\/vnd.ms-powerpoint\",\"application\\/vnd.ms-write\",\"application\\/vnd.ms-excel\",\"application\\/vnd.ms-access\",\"application\\/vnd.ms-project\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.template\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.sheet\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.presentation\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slideshow\",\"application\\/vnd.openxmlformats-officedocument.presentationml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slide\",\"application\\/onenote\",\"application\\/vnd.apple.keynote\",\"application\\/vnd.apple.numbers\",\"application\\/vnd.apple.pages\"],\"lor\":[\"image\\/jpeg\",\"image\\/gif\",\"image\\/png\",\"text\\/csv\",\"text\\/richtext\",\"application\\/rtf\",\"application\\/pdf\",\"application\\/zip\",\"application\\/msword\",\"application\\/vnd.ms-office\",\"application\\/vnd.ms-powerpoint\",\"application\\/vnd.ms-write\",\"application\\/vnd.ms-excel\",\"application\\/vnd.ms-access\",\"application\\/vnd.ms-project\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.document\",\"application\\/vnd.openxmlformats-officedocument.wordprocessingml.template\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.sheet\",\"application\\/vnd.openxmlformats-officedocument.spreadsheetml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.presentation\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slideshow\",\"application\\/vnd.openxmlformats-officedocument.presentationml.template\",\"application\\/vnd.openxmlformats-officedocument.presentationml.slide\",\"application\\/onenote\",\"application\\/vnd.apple.keynote\",\"application\\/vnd.apple.numbers\",\"application\\/vnd.apple.pages\"]}');

UPDATE `settings` SET `value` = '1633' WHERE `shortname` = 'version_db';