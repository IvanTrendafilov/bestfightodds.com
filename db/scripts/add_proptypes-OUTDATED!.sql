LOCK TABLES `prop_types` WRITE;
/*!40000 ALTER TABLE `prop_types` DISABLE KEYS */;
INSERT INTO `prop_types` VALUES (1,'Fight goes to decision','Fight doesn\'t go to decision'),(2,'Fight starts round 2','Fight won\'t start round 2'),(3,'Fight starts round 3','Fight won\'t start round 3'),(4,'Fight starts round 4','Fight won\'t start round 4'),(5,'Fight starts round 5','Fight won\'t start round 5'),(6,'Fight is a draw','Fight is not a draw'),(8,'<T> wins by TKO/KO','Any other result'),(9,'<T> wins by submission','Any other result'),(10,'<T> wins inside distance','Not <T> inside distance'),(11,'<T> wins by decision','Not <T> by decision'),(13,'<T> wins in round 1','Any other result'),(14,'<T> wins in round 2','Any other result'),(15,'<T> wins in round 3','Any other result'),(16,'<T> wins in round 4','Any other result'),(17,'<T> wins in round 5','Any other result'),(18,'<T> wins by 5 round unanimous decision','Any other result'),(19,'<T> wins by 5 round split/majority decision','Any other result'),(20,'<T> wins by 3 round unanimous decision','Any other result'),(21,'<T> wins by 3 round split/majority decision','Any other result'),(22,'Fight ends in round 1','Fight doesn\'t end in round 1'),(23,'Fight ends in round 2','Fight doesn\'t end in round 2'),(24,'Fight ends in round 3','Fight doesn\'t end in round 3'),(25,'Fight ends in round 4','Fight doesn\'t end in round 4'),(26,'Fight ends in round 5','Fight doesn\'t end in round 5');
/*!40000 ALTER TABLE `prop_types` ENABLE KEYS */;
UNLOCK TABLES;


LOCK TABLES `bookies_proptemplates` WRITE;
/*!40000 ALTER TABLE `bookies_proptemplates` DISABLE KEYS */;
INSERT INTO `bookies_proptemplates` VALUES (2,1,'<T> WINS INSIDE DISTANCE',10,'NOT <T> INSIDE DISTANCE',3),(4,1,'<T>/<T> DRAW',6,'FIGHT NOT A DRAW',1),(5,1,'<T> WINS IN ROUND 1',13,'ANY OTHER RESULT',5),(6,1,'<T> WINS IN ROUND 2',14,'ANY OTHER RESULT',5),(7,1,'<T> WINS IN ROUND 3',15,'ANY OTHER RESULT',5),(8,1,'<T> WINS IN ROUND 4',16,'ANY OTHER RESULT',5),(9,1,'<T> WINS IN ROUND 5',17,'ANY OTHER RESULT',5),(11,1,'<T> WINS BY SUBMISSION',9,'ANY OTHER RESULT',5),(12,1,'<T> WINS BY TKO/KO',8,'ANY OTHER RESULT',5),(13,1,'<T> WINS BY 5 RND UNANIMOUS DEC',18,'ANY OTHER RESULT',5),(14,1,'<T> WINS BY 5 RND SPLIT/MAJ DEC',19,'ANY OTHER RESULT',5),(15,1,'<T>/<T> STARTS ROUND 5',5,'FIGHT WON\'T START ROUND 5',1),(16,1,'<T>/<T> STARTS ROUND 4',4,'FIGHT WON\'T START ROUND 4',1),(17,1,'<T>/<T> STARTS ROUND 3',3,'FIGHT WON\'T START ROUND 3',1),(18,1,'<T>/<T> STARTS ROUND 2',2,'FIGHT WON\'T START ROUND 2',1),(20,5,'<T> VS <T> COMPLETES 1 FULL ROUND',2,'<T> VS <T> WON\'T COMPLETE 1 FULL ROUND',2),(21,5,'<T> VS <T> COMPLETES 2 FULL ROUNDS',3,'<T> VS <T> WON\'T COMPLETE 2 FULL ROUNDS',2),(22,5,'<T> VS <T> COMPLETES 3 FULL ROUNDS',4,'<T> VS <T> WON\'T COMPLETE 3 FULL ROUNDS',2),(23,5,'<T> VS <T> COMPLETES 4 FULL ROUNDS',5,'<T> VS <T> WON\'T COMPLETE 4 FULL ROUNDS',2),(24,5,'<T> VS. <T> COMPLETES 1 FULL ROUND',2,'<T> VS. <T> WON\'T COMPLETE 1 FULL ROUND',2),(26,5,'<T> VS. <T> COMPLETES 3 FULL ROUNDS',4,'<T> VS. <T> WON\'T COMPLETE 3 FULL ROUNDS',2),(27,5,'<T> VS. <T> COMPLETES 2 FULL ROUNDS',3,'<T> VS. <T> WON\'T COMPLETE 2 FULL ROUNDS',2),(28,5,'<T> VS. <T> COMPLETES 4 FULL ROUNDS',5,'<T> VS. <T> WON\'T COMPLETE 4 FULL ROUNDS',2),(29,1,'<T> WINS BY <*> ROUND DECISION',11,'NOT <T> BY <*> ROUND DECISION',3),(30,1,'<T>/<T> GOES <*> ROUND DISTANCE',1,'FIGHT WON\'T GO <*> ROUND DISTANCE',1),(33,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT GOES TO DECISION',1,'',1),(34,3,'<*> <T> VS <T> FIGHT OUTCOME FIGHT OUTCOME IS A DRAW',6,'',1),(36,3,'<*> FIGHT OUTCOME <T> WINS BY DECISION',11,'',3),(37,3,'<*> FIGHT OUTCOME <T> WINS BY SUBMISSION',9,'',3),(38,3,'<*> FIGHT OUTCOME <T> WINS BY KO,TKO, OR DQ',8,'',3),(39,2,'<T> WINS INSIDE DISTANCE',10,'NOT GRIFFIN INSIDE DISTANCE',3),(40,2,'<T>/<T> STARTS ROUND 2',2,'FIGHT WON\'T START ROUND 2',1),(41,2,'<T>/<T> STARTS ROUND 3',3,'FIGHT WON\'T START ROUND 3',1),(42,2,'<T>/<T> STARTS ROUND 4',4,'FIGHT WON\'T START ROUND 4',1),(43,2,'<T>/<T> STARTS ROUND 5',5,'FIGHT WON\'T START ROUND 5',1),(44,2,'<T> WINS IN ROUND 1',13,'ANY OTHER RESULT',5),(45,2,'<T> WINS IN ROUND 2',14,'ANY OTHER RESULT',5),(46,2,'<T> WINS IN ROUND 3',15,'ANY OTHER RESULT',5),(47,2,'<T> WINS IN ROUND 4',16,'ANY OTHER RESULT',5),(48,2,'<T> WINS IN ROUND 5',17,'ANY OTHER RESULT',5),(49,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT GOES TO DECISION',1,'',1),(50,7,'<*> FIGHT OUTCOME <T> WINS BY DECISION',11,'',3),(51,7,'<*> FIGHT OUTCOME <T> WINS BY KO, TKO OR DQ',8,'',3),(52,7,'<*> FIGHT OUTCOME <T> WINS BY SUBMISSION',9,'',3),(54,2,'<T>/<T> DRAW',6,'FIGHT NOT A DRAW',1),(55,2,'<T> WINS BY SUBMISSION',9,'ANY OTHER RESULT',5),(56,2,'<T> WINS BY TKO/KO',8,'ANY OTHER RESULT',5),(57,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT ENDS IN RD 1',22,'',1),(58,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT ENDS IN RD 2',23,'',1),(59,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT ENDS IN RD 3',24,'',1),(60,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT ENDS IN RD 4',25,'',1),(61,7,'<*> FIGHT DISTANCE <T> VS <T> FIGHT ENDS IN RD 5',26,'',1),(62,7,'<T> VS <T> FIGHT OUTCOME FIGHT OUTCOME IS A DRAW',6,'',1),(63,2,'<T>/<T> GOES <*> ROUND DISTANCE',1,'FIGHT WON\'T GO <*> ROUND DISTANCE',1),(64,2,'<T> WINS BY <*> ROUND DECISION',11,'NOT <T> BY <*> ROUND DECISION',3),(65,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT ENDS IN RD-3',24,'',1),(66,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT ENDS IN RD-2',23,'',1),(67,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT ENDS IN RD-1',22,'',1),(68,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT ENDS IN RD-4',25,'',1),(69,3,'<*> <T> VS <T> FIGHT DISTANCE FIGHT ENDS IN RD-5',26,'',1);
/*!40000 ALTER TABLE `bookies_proptemplates` ENABLE KEYS */;
UNLOCK TABLES;