UPDATE `acl_permissions` SET `update` =  NULL, `create` = NULL WHERE `resource_type` = 'evaluation' AND `resource_value` IS NULL AND `entity_type` = 'group' AND `entity_value` = 'faculty' AND `assertion` = 'EvaluationReviewer';
UPDATE `acl_permissions` SET `update` =  NULL, `create` = NULL, `delete` = NULL WHERE `resource_type` = 'evaluation' AND `resource_value` IS NULL AND `entity_type` = 'group' AND `entity_value` = 'faculty' AND `assertion` = 'isEvaluated';