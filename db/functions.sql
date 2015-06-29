USE `bets`;
DROP function IF EXISTS `MoneylineToDecimal`;

DELIMITER $$
USE `bets`$$
CREATE DEFINER=`root`@`localhost` FUNCTION `MoneylineToDecimal`(moneyline INT) RETURNS float
BEGIN
	DECLARE decimalval FLOAT;
	IF moneyline = 100 THEN
		SET decimalval = 2.0;
	ELSEIF moneyline > 0 THEN
		SET decimalval = ROUND((moneyline / 100) + 1, 5) ;
	ELSEIF moneyline < 0 THEN
		SET decimalval = ROUND((100 / ABS(moneyline)) + 1, 5);
	END IF;
RETURN decimalval;
END$$

DELIMITER ;

