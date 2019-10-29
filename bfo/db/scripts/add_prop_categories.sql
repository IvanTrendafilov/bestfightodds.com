/* Add categories */
INSERT INTO `prop_categories` (`id`, `category_name`, `category_description`) VALUES (1, 'team_outcome_method', 'How the team will win/lose (e.g. win by KO)');

/* Link existing props to categories */

/* team_outcome_method */
INSERT INTO `prop_type_category` (`proptype_id`, `category_id`) VALUES (8, 1);
INSERT INTO `prop_type_category` (`proptype_id`, `category_id`) VALUES (9, 1);
INSERT INTO `prop_type_category` (`proptype_id`, `category_id`) VALUES (11, 1);
INSERT INTO `prop_type_category` (`proptype_id`, `category_id`) VALUES (18, 1);
INSERT INTO `prop_type_category` (`proptype_id`, `category_id`) VALUES (19, 1);
