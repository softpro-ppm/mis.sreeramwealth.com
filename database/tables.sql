-- Create documents table if not exists
CREATE TABLE IF NOT EXISTS `documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `policy_id` (`policy_id`),
  CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create health_insurance table if not exists
CREATE TABLE IF NOT EXISTS `health_insurance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `coverage_type` varchar(50) NOT NULL,
  `pre_existing_conditions` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `policy_id` (`policy_id`),
  CONSTRAINT `health_insurance_ibfk_1` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create life_insurance table if not exists
CREATE TABLE IF NOT EXISTS `life_insurance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `term_years` int(11) NOT NULL,
  `beneficiaries` text,
  PRIMARY KEY (`id`),
  KEY `policy_id` (`policy_id`),
  CONSTRAINT `life_insurance_ibfk_1` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create general_insurance table if not exists
CREATE TABLE IF NOT EXISTS `general_insurance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `policy_id` int(11) NOT NULL,
  `insurance_type` varchar(50) NOT NULL,
  `property_details` text,
  PRIMARY KEY (`id`),
  KEY `policy_id` (`policy_id`),
  CONSTRAINT `general_insurance_ibfk_1` FOREIGN KEY (`policy_id`) REFERENCES `policies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add updated_at column to policies table if not exists
ALTER TABLE `policies` 
ADD COLUMN IF NOT EXISTS `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP; 