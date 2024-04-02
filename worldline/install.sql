CREATE TABLE IF NOT EXISTS `ps_custom_merchantdetail` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(255) NOT NULL,
  `tpsl_transaction_id` varchar(255) NOT NULL,
  `date_add` datetime ,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;