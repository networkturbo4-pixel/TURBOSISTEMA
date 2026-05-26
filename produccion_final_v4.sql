-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: turbosaas_db
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actas`
--

DROP TABLE IF EXISTS `actas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `folio` varchar(20) NOT NULL,
  `prefijo` varchar(10) DEFAULT 'LIM-',
  `token` varchar(64) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `cliente_nombre` varchar(255) DEFAULT NULL,
  `cliente_dni_ruc` varchar(50) DEFAULT NULL,
  `cliente_direccion` text DEFAULT NULL,
  `cliente_distrito` varchar(100) DEFAULT NULL,
  `cliente_referencia` text DEFAULT NULL,
  `cliente_whatsapp` varchar(50) DEFAULT NULL,
  `cliente_celular_alt` varchar(50) DEFAULT NULL,
  `cliente_gps_lat` varchar(50) DEFAULT NULL,
  `cliente_gps_lng` varchar(50) DEFAULT NULL,
  `foto_rostro_path` varchar(255) DEFAULT NULL,
  `pe_nodo` varchar(100) DEFAULT NULL,
  `pe_nap` varchar(100) DEFAULT NULL,
  `pe_puerto` varchar(50) DEFAULT NULL,
  `pe_potencia` varchar(50) DEFAULT NULL,
  `pe_atenuacion` varchar(50) DEFAULT NULL,
  `srv_fecha` date DEFAULT NULL,
  `srv_hora_inicio` time DEFAULT NULL,
  `srv_hora_fin` time DEFAULT NULL,
  `srv_tipo` varchar(100) DEFAULT NULL,
  `srv_estado` varchar(100) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `red_ssid` varchar(100) DEFAULT NULL,
  `red_password` varchar(100) DEFAULT NULL,
  `red_speed_dl` varchar(50) DEFAULT NULL,
  `red_speed_ul` varchar(50) DEFAULT NULL,
  `red_n_tvs` int(11) DEFAULT NULL,
  `red_splitters` varchar(100) DEFAULT NULL,
  `red_senal_low` varchar(50) DEFAULT NULL,
  `red_senal_high` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `mantenimiento_6_meses` tinyint(1) DEFAULT 0,
  `calificacion_servicio` int(11) DEFAULT 0,
  `firma_cliente` text DEFAULT NULL,
  `firma_tecnico` text DEFAULT NULL,
  `cliente_rotulado` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas`
--

LOCK TABLES `actas` WRITE;
/*!40000 ALTER TABLE `actas` DISABLE KEYS */;
INSERT INTO `actas` VALUES (8,'000001','LIM-','80e21d9b37e7a022b62f47a0d4655c8a','2026-05-13 00:15:23','Javier Mendoza','74589633566','fasfd','Puente Piedra','','95621450000','','','',NULL,'','','','','','2026-05-13','11:48:00','11:48:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,5,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAwMAAACWCAYAAACCYVROAAAQAElEQVR4AezdPW9kVx3H8TtU0FHQICGx+xIiWqQ4LwEpBVRspBQ0dKHGW4MEVAiJiGwFUsRrwBEpkCigo2QRDUUkKOlCvmOO43W8a6/tmblz7wdxdp7uwzmfMw//370zzpc+9T8CBAgQIECAAAECBFYp8KXJ/wgQWJGAoRIgQIAAAQIEPhcQBj63cI0AAQIECCxLwGgIECBwg4AwcAOQhwkQIECAAAECBAgcg8Bd+igM3EXNOgQIECBAgAABAgQWICAMLGASDWGtAsZNgAABAgQIELifgDBwPz9rEyBAgACB/QjYCwECBHYgIAzsANUmCRAgQIAAAQIECNxHYF/rCgP7krYfAgQIECBAgAABAjMTEAZmNiG6s1YB4yZAgAABAgQI7F9AGNi/uT0SIECAwNoFjJ8AAQIzERAGZjIRukGAAAECBAgQILBMgTmPShiY8+zoGwECBAgQIECAAIEdCggDO8S16bUKGDcBAgQIECBA4DgEhIHjmCe9JECAAIG5CugXAQIEjlhAGDjiydN1AgQIECBAgACB/QosbW/CwNJm1HgIECBAgAABAgQI3FJAGLgllMXWKmDcBAgQIECAAIHlCggDy51bIyNAgACB1xWwPAECBFYmIAysbMINlwABAgQIECBA4FzAv9MkDHgWECBAgAABAgQIEFipgDCw0olf57CNmgABAgQIECBA4LKAMHBZw3UCBAgQWI6AkRAgQIDAjQLCwI1EFiBAgAABAgQIEJi7gP7dTUAYuJubtQgQIECAAAECBAgcvYAwcPRTuNYBGDcBAgQIECBAgMB9BYSB+wpanwABAgR2L2APBAgQILATAWFgJ6w2SoAAAQIECBAgcFcB6+1PQBjYn7U9ESBAgAABAgQIEJiVgDAwq+lYa2eMmwABAgQIECBA4BACwsAh1O2TAAECaxYwdgIECBCYjYAwMJup0BECBAgQIECAwPIEjGjeAsLAvOdH7wgQIECAAAECBAjsTEAY2BntWjds3AQIECBAgAABAsciIAwcy0zpJwECBOYooE8ECBAgcNQCwsBRT5/OEyBAgAABAgT2J2BPyxMQBpY3p0ZEgAABAgQIECBA4FYCwsCtmNa6kHETIECAAAECBAgsWUAYWPLsGhsBAgReR8CyBAgQILA6AWFgdVNuwAQIECBAgACBaWJAIAFhIAWNAAECBAgQIECAwAoFhIHVTLqBEiBAgAABAgQIEHhRQBh40cMtAgQILEPAKAgQIECAwC0EhIFbIFmEAAECBAgQIDBnAX0jcFcBYeCuctYjQIAAAQIECBAgcOQCwsBRTqBOEyBAgAABAgQIELi/gDBwf0NbIECAwG4FbJ0AAQIECOxIQBjYEazNEiBAgAABAgTuImAdAvsUEAb2qW1fBAgQIECAAAECBGYkIAwcfDJ0gAABAgQIECBAgMBhBISBw7jbKwECaxUwbgIECBAgMCMBYWBGk6ErBAgQIECAwLIEjIbA3AWEgbnPkP4RIECAAAECBAgQ2JGAMPCgsDZGgAABAgQIECBA4HgEhIHjmSs9JUBgbgL6Q4AAAQIEjlxAGDjyCdR9AgQIECBAYD8C9kJgiQLCwBJn1ZgIECBAgAABAgQI3EJAGHgpkgcIECBAgAABAgQILFtAGFj2/BodAQK3FbAcAQIECBBYoYAwsMJJN2QCBAgQILB2AeMnQOBcQBg4d/AvAQIECBAgQIAAgdUJrCQMrG5eDZgAAQIECBAgQIDAjQLCwI1EFiBA4OgEdJgAAQIECBC4lYAwcCsmCxEgQIAAAQJzFdAvAgTuLiAM3N3OmgQIECBAgAABAgSOWuAIw8BRe+s8AQIECBAgQIAAgdkICAOzmQodIUDgWgF3EiBAgAABAjsTEAZ2RmvDBAgQIECAwOsKWJ4Agf0KCAP79bY3AgRuEPjd73431W5YzMMECBAgQIDAAwgcOAw8wAhsggCBxQj89Kc/nb73ve9tm0CwmGk1EAIECBCYsYAwMOPJ0TUCixO4YUCffPLJxRI//OEPp+fPn1/cdoUAAQIEdiMw3mu7rJ2dnU1nn7WnT59Oo7377rsX1995553pahvLdVCndWu76a2tPrSAMPDQorZHgMCdBX7wgx9MX/va17brFwyePXu2ve4fAgSOU0Cv5ycwiv2K97feemv6yle+Mj1+/HjabDbby653f+309HQ6/X97//33L65/8MEH0wdX2ljuRz/60dS6tc3mfJtdH+Gh/Y51z87O5ge0wh4JAyucdEMmMFeBR48eTX/+85+nLuvj6WcfQn1wdF0jQIAAgdsLVPTXeg+tGK/I32w+L857f60Y/+9//3v7jd5hyfrQfkYAaL8jGNSvzebzPtXXziy0zh12ZZU7CjxgGLhjD6xGgACBSwIFgSdPnlzc0weHD4YLDlcIECDwgkDvj7UK6Vpf56nIrvivjffQ8d7a+2v31X7zm99Mv/3tb6e///3v0x/+8Idt6/rl1v0/+clPppOTk+ntt9+eLq/fNmrdV+vxsVyXL3T0FTfqf4GhbXVmoX5vNuchoeuNZwSILhvnCBddtu5or9iNh14iIAy8BMbdBAjcILDDh3/84x9vP3DGLvog6MNi3HZJgACBNQn0/lexWxFc6z2xInmz+bxgrpCu/f73v9/+3qrrFfKffvrpRbFf8V/rPbZWAf/d7353eza24r326NGj7e1H/7/svvfee28bFD788MPp8vpto9Z9tR5vn+Ny7Ltw0f0tU2u/tbZda1/Xzefz58+3Y2nsFf2jNbZCwWh5jNYy123LfS8XEAZebuMRAgQOKNAHxviAeP7ZB0Jv+l0esEt2TWDVAga/P4He6yr6a5vNecFfsVuhW2Hce2PFdO+TFdkV2xXetX//+9/b4r8ivUJ7f72+fk/1tVZf6nOtftfqe63+j9aZih6rtWzr1drG9Xtw730FhIH7ClqfAIGdCfRhMD4A+gD0g+KdUdswAQIHFBjFfwX/ZnNe/Ff416WOglcwV+hXMHe998aK/VEsj/fJlj/W1hhqnaloXLXG2XhrjT2DWtdH67HaWLb1jtXgUP1+SRg4VHfslwABAp8LdDTo8ht7H4odKft8CdcIECBwXAIV/hX6vZddLf57z6uwHQVvBX+t+49rlLvtbaFhtGxqfVZ0uds9L3PrwsAy59WoCLyewIyX7oOwN/nRRYFgSLgkQGDuAhX+P//5z7d/n/9y4V8QqO8Vr1eL/+7rMY3AvgSEgX1J2w8BAncW6PRvR4HGBgSCIeGSwN0ErLU7gefPn18U//3I91e/+tX2R7AV+VcL/w52dP/uemPLBG4WEAZuNrIEAQIzEOhDVCCYwUToAgECXxC4GgD6GlBFft9r/9vf/nbxF3i67wsru4PA7gVeuQdh4JU8HiRAYC4CBYHLXxeqX50h6EO46xoBAgT2KdB7T0V/X//pDEC3K/Yvf9+/96199sm+CNxFQBi4i5p1CMxZYMF965R6AeDyEPsQ/vjjjy/f5ToBAgR2InB2drb9ClDvO4WAf/zjH9uj/gWAvs7Ye9ROdmyjBHYoIAzsENemCRB4eIE+bK8ebfvOd76z/U7uw+/NFgnMX0APdyfQ0f5+7FsrAIw/b9zXFvsK0HXvR7vrjS0T2I2AMLAbV1slQGCHAn0Qf/nLX77YwyeffDJ1lK4P7os7XSFAgMAdBHofqfjvPaXWJt58883tf8hrHP2/ekCiZTQCexJ48N0IAw9OaoMECOxaoA/iX/7yly/spg/wPri7fOEBNwgQIHCDQO8bBYCO/r/zzjvbpTvoMI7+91uA7Z3+IbBAAWFggZNqSAsSMJSXCjx58mT7Xd3LC/SB/tZbb12+y3UCBAhcK9D7xQgA432j4r8Q0Nd/rl3JnQQWKCAMLHBSDYnAWgQKBKenpy8Mtw/48cH+wgNuEDgCAV3crUDvDwWAjv7X2lvFfyFAAEhDW6OAMLDGWTdmAgsS6AP8aiDoL36MD/oFDdVQCBC4o0DvCYWAcaCg7/4XAnr/6GuHd9ys1QjcV2AW6wsDs5gGnSBA4D4CfaBfDQT9/e8+/O+zXesSIHC8AuMsQL8D+Oijj6bvf//72x8B935xvKPScwIPLyAMPLypLRK4XsC9OxXog/7k5OSFfRQQnj59+sJ9bhAgsGyBQsA4GPDNb37zIgA8evRo2QM3OgJ3FBAG7ghnNQIE5iXQB32n/ru83DOB4LKG6/sUsK/9CRQACv6dBei/BdCBgd4P+l3R/nphTwSOU0AYOM5502sCBK4RKAj0PeAuLz8sEFzWcJ3AMgQKAJ0B6HcAv/jFLyZnAZYxr0c8iqPtujBwtFOn4wQIXCdQELguEFQ0dOTwunXcR4DA8QgUAvoDAYWAet3r/Wc/+9nkLEAaGoHXFxAGXt/MGgSmicGsBR49ejRVIHQ5OloBIRAMDZcEjkfg8mt3fA2o3wj150AFgOOZRz2dr4AwMN+50TMCBO4hUBB4WSDozwzeY9NWXaGAIe9foPDeGYACQGf1Ln8NqN8E7L9H9khgmQLCwDLn1agIEPhM4GWBoAJDIPgMyP8JzEyg12Wvz81mMxUAeg1/+umn278I5CzAzCZr2d1Z1eiEgVVNt8ESWJ9AxcR1ZwgqOJ4/f74+ECMmMDOBXocV/pvNZup1OV6zfQ3IfxNgZpOlO4sUEAYWOa0G9VoCFl68wCguuhyDrQDpB4hdjvtcEiCwH4FedyMA9DWgvhLUX/0aAcDXgPYzD/ZCIAFhIAWNAIHFCxQEOkNwucioIBEIFj/1XxigOw4j0OvtugAwvgbkLMBh5sVeCQgDngMECKxGoEDQf4ioI5Bj0BUoAsHQcEngYQV6fV0XADoDUBMAHtbb1q4VcOcNAsLADUAeJkBgWQIFgv4soUCwrHk1mvkIjADQ139qvdb68W9BfASAXofz6bGeEFi3gDCw7vlf3uiNiMAtBCpEOiJZkTIWr4DpDMG47ZIAgdsL9PrpDEDFf228trrsa0AFgQLB7bdoSQIE9iUgDOxL2n4IEJidQIGg3xEUDupcBU2FTJfd1uYvoIeHE+h18qoAMM4CHK6H9kyAwG0EhIHbKFmGAIHFCvSD4quBwBmCxU63gd1TYASAXiMF5478t8kuK/5rhezu0wjsQMAmdyAgDOwA1SYJEDgugc4MFAjG1xgqeCp2jmsUektgNwK9HjoD0GtiBIDuKwD0uhkBoNfRbnpgqwQI7FJAGNilrm3fT8DaBPYoUCHTEc0KnHZ7dnY2Vfh0XSOwNoGK/QJA/xGwXgenp6dTr4kCc9//HwHg5ORkbTTGS2BxAsLA4qbUgAgQuKvA1UBQQdTR0Ltuz3qvJ2DpwwtU8BcCRgDoPwbW66Iw4IfAh58fPSCwCwFhYBeqtkmAwFELdIagI58NouLojTfemAoG3dYILE2g53YBYLPZTIXfCv/LAaDXQq+JpY3beA4uoAMzERAGZjIRukGAwLwEKoYqgr761a9Of/3rNKZDOgAACIxJREFUX6dnz54JBPOaIr25h8DlAPD48ePpcgDoeV8TAO4BbFUCRyQgDBzRZB11V3WewBEKFAj+8pe/TF1WLAkERziJunwhMAJAxX+t5/R4blf81woA3XexkisECCxeQBhY/BQbIAEC9xGoMOovpnRZ8dTXKSqq7rPNNaxrjPMQ6Lnac7av/4wAUM96Lve8FgDS0AisW0AYWPf8Gz0BArcQKAhUOHXZDyorrPotwS1WtQiBgwj0PL36l4D6yz/+EtBBpmMNOzXGIxYQBo548nSdAIH9CRQECgQVVB1tLRDUCgXd3l9P7InA9QI9DzsLsNlspoJAgaDnbWcB+ktAPX/706DXr+1eAgTWKiAMrHXm7zNu6xJYqUCFVQVVR1e7XhAoEIzCq2JspTSGfSCBnnMjAIyvAfXcHAFgfA3oQN2zWwIEjkBAGDiCSdJFAgTmJdDR1YqsLiu8CgUFgv4EaYVZt+fV4/v1xtrzEhgBoOK/VuHf87DLnpe1fgg8r17rDQECcxUQBuY6M/pFgMDsBTpD0JmCt99+e9vX//znP9s/0djZgoq0Dz74YHu/fwjcV2AEgPHcqvBvm132HBwBoFDQ/RqBewhYdWUCwsDKJtxwCRB4WIGKrw8//HCqGKswG1uveOtsQaGgswXdHo+5JHBbgZ4377777tTzqOfX2dnZxZ+67TnXGYB+x3Lb7VmOAAECVwWEgasia7ttvAQIPIhAoaDCrAKtMwajQKuYOz09nTqiWzg4Ozt7kP3ZyLIFet4UIgsB77///vSNb3xje9apHwL3HOu5tmwBoyNAYF8CwsC+pO2HAIFVCBQK+i3B+OpG1xt4xV1fGyoUVOBV6HVfj+2z2de8BXqOFBp7jnT99LMgWfH/z3/+cxIA5j13ekfgWAWEgWOdOf0mQGD2AgWDzhJUzFXUOVsw+ynbewcLhLXC4WazmbqsEz1fet4UAHoedZ9G4A4CViFwo4AwcCORBQgQIHA/gYq5ijpnC+7nuJS1+6pYRf84S9SZgMY2nh8FyJ4v3acRIEBg1wLCwK6F97l9+yJAYPYCBYOKvY76dvS323W6o8PdrkCsOOwrIt2vHbdA8/rxxx9vj/gXAPr6z0cffbQdVM+DfgNQCKj4H2eOtg/6hwABAnsSEAb2BG03BAgQuCxQCKgALBRUDF79bUGBoMLx6dOnU0eSL687rruch0AFf615ar4KdM1dl83jn/70p21H33zzze1fnWreaz0Htg/4hwABAgcUEAYOiG/XBAgQSODk5GTqKHHBoMtud38F5jhbUHFZodl9PaYdRiD/zto0FxX6FfzNTa3bPd78FfBGe++996aK/+4/TK/tdSEChkFgJwLCwE5YbZQAAQKvL9CR4idPnkwVkQWDgkD3taWKzG5XfNYqSLtfe3iBrGsV/LW8N5vNtNlstn/vv6I//+bm5LMg13z1dZ/mrDBX4d9jD98zWyRAgMDDCwgDD2/6MFu0FQIEVi1QMVlRWaFZKyQEUpHa11EqSDsaXaFaYdpj2usJDMsK/lqWm815wZ9t4WvYdr3WXIzCv/mpFQheb8+WJkCAwHwEhIH5zIWeECCwYoGXDb1QULHZEedx5FkweJnW9fe/qugvAFTk11ou767XKvwz77Kiv9ZcXL8X9xIgQOA4BYSB45w3vSZAYIUCFaoFgREMKlIrWitQK2SvnjHoaHf3rYFqjL8x1yryO7q/2Zwf6e92VrVMssyt2zlW9I9W0V/r8TXYGeNBBOyUwGwEhIHZTIWOECBA4PYCo5itaB3FbCGhAnYUxhW6FcEVxRXIFcG338P8lhzjaiy1virV+Dab6wv+ls+plkUtq/E1n67nl1nLzG/EekSAAIHdCwgDuzeeJvsgQIDAjgUqZjtrUIHbEe4uu12hW1FcIVzhXDCoiJ5jMKiftfpWsV8bfd5svljw933+lo228TfWxllr/KPoz6Oiv9YyLa8RIECAwLmAMHDu4F8CBAg8mMChNzQK484UVBRXDHe9cFDfKqJHkb3vYHBdsV9fNpvzYr+w0u0K+lrFfuvU78ZVq6DvsVrjG0V/1yv4ay3TOhoBAgQIvFpAGHi1j0cJECBw9AIV0AWBAkHBoNb1CuarwaDbdx1wRXutAr7tdGS/sFFxX5G/2ZwX/N2ukK+1bG3ss77Wr/rb4/WzIn8U/PW92xX8tZYd67okcCABuyVw1ALCwFFPn84TIEDg9QUquCu2K7RHcd3tCvmK9wr3N954Y6qgr3X/y/bSYxX9X//617d/g//x48dTxX7bqZhv/Yr9lmsb7bv25MmTqcdrFff1YxT83a5vFfstd3Jy0qoaAQIECOxAQBh4XVTLEyBAYEECFeYV2xXeFeEV5V3/1re+NT179myqqK/AH61Cf7TN5vxIfwX9v/71r61K26tVxHd/bWx3FPvtYxT77av9t852A/4hQIAAgb0KCAN75bYzAgSOTWBt/a0or5D/9a9/PVXEjwJ+FO89PkxarmK/9sc//nEayyr2h5BLAgQIzF9AGJj/HOkhAQIEDipQAOjofcV/oaCQUOt6R/Zr3/72tw/aRzsn8EACNkNgdQLCwOqm3IAJECBAgAABAgQInAusOwycG/iXAAECBAgQIECAwCoFhIFVTrtBE1ingFETIECAAAECLwoIAy96uEWAAAECBAgsQ8AoCBC4hYAwcAskixAgQIAAAQIECBBYosBywsASZ8eYCBAgQIAAAQIECOxQQBjYIa5NEyCwOwFbJkCAAAECBO4vIAzc39AWCBAgQIAAgd0K2DoBAjsSEAZ2BGuzBAgQIECAAAECBOYuMM8wMHc1/SNAgAABAgQIECCwAAFhYAGTaAgEjl1A/wkQIECAAIHDCAgDh3G3VwIECBAgsFYB4yZAYEYCwsCMJkNXCBAgQIAAAQIECOxT4H8AAAD//0nhGqAAAAAGSURBVAMATrquTT2Jk+wAAAAASUVORK5CYII=','data:image/png;base64,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',NULL),(9,'000009','LIM-','554e0c958202e4a881eb6d982a442715','2026-05-16 17:29:57','Test Cronometro','12345678','Calle Test 123','','','','','','',NULL,'','','','','','2026-05-16','12:29:00','00:00:00','Instalación','Finalizada (Éxito)',4,'','','','',0,'','','','',0,0,'','',''),(10,'000010','LIM-','b0a00f26b354f34718880ea69a514c49','2026-05-16 18:01:00','Luis Lopez','121565656565656565656565656123','','','','','','','',NULL,'','','','','','2026-05-16','00:30:00','00:00:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,0,'','',''),(11,'000011','LIM-','166e28f7a9bf2a4b2d828b9e0691a320','2026-05-16 20:40:49','Javier Mendoza','','','','','','','','',NULL,'','','','','','2026-05-16','00:33:00','00:00:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,0,'','','');
/*!40000 ALTER TABLE `actas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actas_equipos`
--

DROP TABLE IF EXISTS `actas_equipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_equipos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `modelo_marca` varchar(150) DEFAULT NULL,
  `serie_mac` varchar(150) DEFAULT NULL,
  `propiedad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_equipos_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas_equipos`
--

LOCK TABLES `actas_equipos` WRITE;
/*!40000 ALTER TABLE `actas_equipos` DISABLE KEYS */;
INSERT INTO `actas_equipos` VALUES (11,8,'Instala','Cinta Negra','','Alquiler');
/*!40000 ALTER TABLE `actas_equipos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actas_fotos`
--

DROP TABLE IF EXISTS `actas_fotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_fotos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `ruta_archivo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_fotos_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas_fotos`
--

LOCK TABLES `actas_fotos` WRITE;
/*!40000 ALTER TABLE `actas_fotos` DISABLE KEYS */;
/*!40000 ALTER TABLE `actas_fotos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `actas_materiales`
--

DROP TABLE IF EXISTS `actas_materiales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actas_materiales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `acta_id` int(11) NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `cantidad` decimal(10,2) DEFAULT NULL,
  `unidad` varchar(50) DEFAULT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `propiedad` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `acta_id` (`acta_id`),
  CONSTRAINT `actas_materiales_ibfk_1` FOREIGN KEY (`acta_id`) REFERENCES `actas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas_materiales`
--

LOCK TABLES `actas_materiales` WRITE;
/*!40000 ALTER TABLE `actas_materiales` DISABLE KEYS */;
INSERT INTO `actas_materiales` VALUES (3,8,'Cinta Negra',10.00,'Unidades','Instala','Alquiler'),(6,10,'Conectores CATV',2.00,'Unidades','Instala','Alquiler');
/*!40000 ALTER TABLE `actas_materiales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clientes`
--

DROP TABLE IF EXISTS `clientes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_completo` varchar(255) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `celular` varchar(20) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `referencia` text DEFAULT NULL,
  `detalles_plan` text DEFAULT NULL,
  `fecha_servicio_contratado` datetime DEFAULT NULL,
  `inicio_servicio` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Miguel Rivera Lopez','7895412023','987456321','miguelad@gmail.com','afdagf','agag','','2026-05-12 19:03:00','2026-05-31 19:03:00','2026-05-13 00:03:26',NULL),(2,'Javier Mendoza','74589633566','95621450000','','fasfd','','Plan Full - 700 Mbps ','2026-05-13 11:48:00','2026-05-13 11:48:00','2026-05-13 00:15:23',5),(3,'MENDOZA CASTRO CESAR ALEXANDER','10742146362','95621450000','','AV. LA GRAMA MZA. 01 LOTE. 1 LOS BALCONES','','',NULL,NULL,'2026-05-13 01:25:52',6),(4,'Test Cronometro','12345678','',NULL,'Calle Test 123','','','2026-05-16 12:29:00','2026-05-16 12:29:00','2026-05-16 17:29:57',8),(5,'Luis Lopez','12156565656565656565','',NULL,'','','','2026-05-16 00:00:00','2026-05-16 00:00:00','2026-05-16 18:01:00',9),(6,'Luis Lopez','12156565656565656565','',NULL,'','','','2026-05-16 00:30:00','2026-05-16 00:30:00','2026-05-17 05:30:26',11),(7,'Luis Lopez','12156565656565656565','',NULL,'','','','2026-05-16 00:30:00','2026-05-16 00:30:00','2026-05-17 05:30:41',13);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_categories`
--

DROP TABLE IF EXISTS `inventory_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_categories`
--

LOCK TABLES `inventory_categories` WRITE;
/*!40000 ALTER TABLE `inventory_categories` DISABLE KEYS */;
INSERT INTO `inventory_categories` VALUES (1,'Modem','2026-05-12 20:40:59'),(2,'Router','2026-05-12 21:03:44'),(3,'Herramientas','2026-05-12 21:28:26'),(4,'Materiales','2026-05-12 22:33:36'),(5,'TV','2026-05-12 23:04:15'),(6,'—','2026-05-21 22:56:59');
/*!40000 ALTER TABLE `inventory_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_entries`
--

DROP TABLE IF EXISTS `inventory_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_entries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `tipo` enum('entrada','salida','devolucion','reparacion') DEFAULT 'entrada',
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `inventory_entries_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_entries`
--

LOCK TABLES `inventory_entries` WRITE;
/*!40000 ALTER TABLE `inventory_entries` DISABLE KEYS */;
INSERT INTO `inventory_entries` VALUES (25,1024,1,'entrada','','2026-05-21 04:02:48'),(26,1024,1,'entrada','','2026-05-21 04:02:53'),(27,1027,1,'entrada','asfasf','2026-05-21 16:41:45'),(28,931,1,'entrada','asdfafasf','2026-05-21 16:54:22'),(29,1025,1,'entrada','dsfsdf','2026-05-21 16:54:57'),(30,1028,1,'entrada','sdfggsdg','2026-05-21 21:22:13'),(31,1028,1,'entrada','','2026-05-21 21:22:22'),(32,1030,1,'salida','se entrego a jose','2026-05-21 21:38:10'),(33,941,1,'salida','sdfgsdhgsdfhdsfhdsfhdsfhsdhf','2026-05-21 21:39:20'),(35,909,1,'entrada','asfasf','2026-05-21 23:37:47'),(36,1037,1,'entrada','','2026-05-22 02:56:43');
/*!40000 ALTER TABLE `inventory_entries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_entry_photos`
--

DROP TABLE IF EXISTS `inventory_entry_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_entry_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `entry_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `entry_id` (`entry_id`),
  CONSTRAINT `inventory_entry_photos_ibfk_1` FOREIGN KEY (`entry_id`) REFERENCES `inventory_entries` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_entry_photos`
--

LOCK TABLES `inventory_entry_photos` WRITE;
/*!40000 ALTER TABLE `inventory_entry_photos` DISABLE KEYS */;
INSERT INTO `inventory_entry_photos` VALUES (4,25,'uploads/inventario/inv_25_6a0e83e8b2261.jpg','2026-05-21 04:02:48'),(5,28,'uploads/inventario/inv_28_6a0f38bed20a5.jpg','2026-05-21 16:54:22'),(6,29,'uploads/inventario/inv_29_6a0f38e1803b3.jpg','2026-05-21 16:54:57'),(7,30,'uploads/inventario/inv_30_6a0f778548d42.jpg','2026-05-21 21:22:13'),(8,32,'uploads/inventario/inv_32_6a0f7b4201dde.png','2026-05-21 21:38:10'),(9,33,'uploads/inventario/inv_33_6a0f7b8885353.jpeg','2026-05-21 21:39:20'),(11,35,'uploads/inventario/inv_35_6a0f974b0985b.jpg','2026-05-21 23:37:47');
/*!40000 ALTER TABLE `inventory_entry_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_product_photos`
--

DROP TABLE IF EXISTS `inventory_product_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_product_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `inventory_product_photos_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_product_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_product_photos`
--

LOCK TABLES `inventory_product_photos` WRITE;
/*!40000 ALTER TABLE `inventory_product_photos` DISABLE KEYS */;
INSERT INTO `inventory_product_photos` VALUES (1,34,'uploads/product_images/prod_34_6a08e289b75e6.jpeg',1,'2026-05-16 21:32:57'),(2,34,'uploads/product_images/prod_34_6a08e289b77f9.jpeg',1,'2026-05-16 21:32:57'),(3,34,'uploads/product_images/prod_34_6a08e289b8073.jpeg',1,'2026-05-16 21:32:57'),(4,35,'uploads/product_images/prod_35_6a0e6d6901e4f.jpg',1,'2026-05-21 02:26:49');
/*!40000 ALTER TABLE `inventory_product_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_products`
--

DROP TABLE IF EXISTS `inventory_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `master_sku` varchar(50) DEFAULT NULL,
  `product_type` varchar(20) DEFAULT 'normal',
  `parent_product_id` int(11) DEFAULT NULL,
  `variant_brand` varchar(100) DEFAULT NULL,
  `variant_size` varchar(100) DEFAULT NULL,
  `variant_attributes` longtext DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) DEFAULT NULL,
  `total_quantity` int(11) DEFAULT 0,
  `stock_minimo` int(11) DEFAULT 10,
  `stock_critico` int(11) DEFAULT 3,
  `custom_columns` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_columns`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_bulk` tinyint(1) DEFAULT 0,
  `unit_type` varchar(50) DEFAULT 'Unidades',
  `requires_photos` tinyint(1) DEFAULT 0,
  `product_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_sku` (`master_sku`),
  KEY `category_id` (`category_id`),
  KEY `fk_parent_product` (`parent_product_id`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=42 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_products`
--

LOCK TABLES `inventory_products` WRITE;
/*!40000 ALTER TABLE `inventory_products` DISABLE KEYS */;
INSERT INTO `inventory_products` VALUES (28,NULL,'normal',NULL,NULL,NULL,NULL,'Cable Coaxial','Nombres alternativos: luis,manuel',NULL,10,10,3,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-13 22:30:11',0,'Unidades',0,NULL),(29,'BLK-FAN97','granel',NULL,NULL,NULL,NULL,'Producto a Granel Test','',4,200,10,3,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-13 23:20:46',1,'Unidades',0,NULL),(30,'BLK-3Q662E','granel',NULL,NULL,NULL,NULL,'Cable Coaxial','',NULL,100,10,3,'[]','2026-05-16 16:07:25',1,'Unidades',0,NULL),(31,'BLK-QUYPJ3','granel',NULL,NULL,NULL,NULL,'Cinta Negra','',4,108,10,3,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-16 16:08:07',1,'Unidades',1,NULL),(32,'BLK-8T2GFQ','granel',NULL,NULL,NULL,NULL,'Conectores CATV','',4,160,10,3,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-16 17:57:36',1,'Unidades',1,'uploads/product_photos/product_32_6a0f374ac5ae3.jpg'),(33,NULL,'normal',NULL,NULL,NULL,NULL,'Router TP-Link AC1200','Router TP-Link de doble banda 2.4GHz/5GHz, velocidad hasta 1200 Mbps',NULL,10,5,2,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-16 20:56:29',0,'Unidades',0,'uploads/product_images/prod_33_6a08da3a646f0.jpeg'),(34,NULL,'normal',NULL,NULL,NULL,NULL,'Producto Simple 0002 (Edited)','Test desc edited',4,10,5,2,'[\"SN\",\"IP\",\"Modelo\",\"Color\",\"Ubicaci\\u00f3n\",\"Talla 41\",\"Talla 42\",\"Talla 43\"]','2026-05-16 21:32:57',0,'Unidades',1,'uploads/product_images/prod_34_6a08e289b75e6.jpeg'),(35,NULL,'normal',NULL,NULL,NULL,NULL,'Producto a Granel Test','',NULL,100,10,3,'[]','2026-05-21 02:26:49',0,'Unidades',0,'uploads/product_images/prod_35_6a0e6d6901e4f.jpg'),(37,'BLK-KSHAT4','granel',NULL,NULL,NULL,NULL,'Zapato','',4,9,10,3,'[]','2026-05-21 23:42:53',1,'Unidades',0,NULL),(38,NULL,'agrupado',NULL,NULL,NULL,NULL,'Mouse Gamer','',4,0,10,3,'[{\"name\":\"Marca\",\"type\":\"text\"},{\"name\":\"Talla\",\"type\":\"text\"},{\"name\":\"Talla 41\",\"type\":\"text\"}]','2026-05-22 05:20:42',0,'Unidades',0,NULL),(39,NULL,'granel',38,'Micronics','','{\"Marca\":\"Micronics\"}','Mouse Gamer','',4,10,10,3,NULL,'2026-05-22 05:20:42',1,'Unidades',0,NULL),(40,NULL,'granel',38,'Samsung','','{\"Marca\":\"Samsung\"}','Mouse Gamer','',4,15,10,3,NULL,'2026-05-22 05:20:42',1,'Unidades',0,NULL),(41,NULL,'granel',38,'LG','','{\"Marca\":\"LG\"}','Mouse Gamer','',4,18,10,3,NULL,'2026-05-22 05:20:42',1,'Unidades',0,NULL);
/*!40000 ALTER TABLE `inventory_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_sku_photos`
--

DROP TABLE IF EXISTS `inventory_sku_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_sku_photos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) NOT NULL,
  `ruta_archivo` varchar(255) NOT NULL,
  `uploaded_by` int(11) NOT NULL,
  `nota` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `uploaded_by` (`uploaded_by`),
  CONSTRAINT `inventory_sku_photos_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_sku_photos_ibfk_2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_sku_photos`
--

LOCK TABLES `inventory_sku_photos` WRITE;
/*!40000 ALTER TABLE `inventory_sku_photos` DISABLE KEYS */;
INSERT INTO `inventory_sku_photos` VALUES (3,1025,'uploads/sku_photos/sku_1025_6a0e77ffd7702.jpeg',1,NULL,'2026-05-21 03:11:59'),(4,1025,'uploads/sku_photos/sku_1025_6a0e78a64393b.jpeg',1,NULL,'2026-05-21 03:14:46'),(5,1030,'uploads/sku_photos/sku_1030_6a0e78b52052d.jpeg',1,NULL,'2026-05-21 03:15:01'),(6,1027,'uploads/sku_photos/sku_1027_6a0e78bfa5c54.jpg',1,NULL,'2026-05-21 03:15:11'),(7,1027,'uploads/sku_photos/sku_1027_6a0e7d2009d3e.jpeg',1,NULL,'2026-05-21 03:33:52'),(8,1024,'uploads/sku_photos/sku_1024_6a0e7de535401.webp',1,NULL,'2026-05-21 03:37:09'),(9,931,'uploads/sku_photos/sku_931_6a0e81e67d85f.png',1,NULL,'2026-05-21 03:54:14'),(10,1027,'uploads/sku_photos/sku_1027_6a0f388e42d81.jpg',1,NULL,'2026-05-21 16:53:34'),(11,1028,'uploads/sku_photos/sku_1028_6a0f77193282e.jpeg',1,NULL,'2026-05-21 21:20:25'),(12,1025,'uploads/sku_photos/sku_1025_6a0f77f4d8fa7.jpg',1,'ertedgdfdh','2026-05-21 21:24:04'),(13,941,'uploads/sku_photos/sku_941_6a0f7b728314e.jpg',1,NULL,'2026-05-21 21:38:58');
/*!40000 ALTER TABLE `inventory_sku_photos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_skus`
--

DROP TABLE IF EXISTS `inventory_skus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_skus` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `sku_code` varchar(20) NOT NULL,
  `status` enum('disponible','instalado','malogrado','reparado','en_transito') DEFAULT 'disponible',
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `historia` enum('ninguno','devuelto','malogrado','antiguo','en_transito') DEFAULT 'ninguno',
  `is_epp` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_code` (`sku_code`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_skus_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1279 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_skus`
--

LOCK TABLES `inventory_skus` WRITE;
/*!40000 ALTER TABLE `inventory_skus` DISABLE KEYS */;
INSERT INTO `inventory_skus` VALUES (909,28,'TRB-PMTSWH','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',1,'ninguno',0),(910,28,'TRB-PR7A5L','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(911,28,'TRB-6Z667W','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(912,28,'TRB-KHBNX2','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(913,28,'TRB-NEZ9ZM','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(914,28,'TRB-YYLVVT','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(915,28,'TRB-6FDF4G','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(916,28,'TRB-QL7AKG','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(917,28,'TRB-87FMLT','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(918,28,'TRB-29P64G','disponible','{\"SN\":\"\",\"IP\":\"\"}','2026-05-13 22:30:11',NULL,'ninguno',0),(919,33,'TRB-JCBXKZ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(920,33,'TRB-FS84YV','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(921,33,'TRB-X6ZHFS','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(922,33,'TRB-ZA6HJS','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(923,33,'TRB-DHV79J','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(924,33,'TRB-9AQMES','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(925,33,'TRB-YY78AY','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(926,33,'TRB-PNQ8XW','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(927,33,'TRB-2BKHYT','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(928,33,'TRB-FVUP29','disponible','{\"SN\":\"\",\"IP\":\"\",\"Ubicaci\\u00f3n\":\"\"}','2026-05-16 20:56:29',NULL,'ninguno',0),(929,34,'TRB-FDJMAP','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(930,34,'TRB-HL5SCA','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(931,34,'TRB-PMHKQQ','disponible','[]','2026-05-16 21:32:57',4,'ninguno',0),(932,34,'TRB-87QKL5','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(933,34,'TRB-99MHGN','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(934,34,'TRB-P7ULNH','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(935,34,'TRB-5D2YUS','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(936,34,'TRB-X6DSJ4','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(937,34,'TRB-9W79PA','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(938,34,'TRB-YYYQHY','disponible','[]','2026-05-16 21:32:57',NULL,'ninguno',0),(939,35,'TRB-JSKQU2','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(940,35,'TRB-K4VBLW','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(941,35,'TRB-328PRJ','en_transito','[]','2026-05-21 02:26:49',3,'en_transito',0),(942,35,'TRB-2WPLW6','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(943,35,'TRB-3V9PSZ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(944,35,'TRB-4H68AF','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(945,35,'TRB-V8T4CJ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(946,35,'TRB-DKWGRZ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(947,35,'TRB-S2733A','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(948,35,'TRB-NYQPUK','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(949,35,'TRB-M9M8YP','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(950,35,'TRB-XSSGJS','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(951,35,'TRB-WUA6VN','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(952,35,'TRB-48KVJV','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(953,35,'TRB-Z5J933','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(954,35,'TRB-ZRZVDU','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(955,35,'TRB-WCBKYG','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(956,35,'TRB-W77LYE','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(957,35,'TRB-DQW8SZ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(958,35,'TRB-LUQ4HY','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(959,35,'TRB-ZDNPL9','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(960,35,'TRB-84KA7U','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(961,35,'TRB-RTL692','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(962,35,'TRB-3TJP5J','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(963,35,'TRB-SB4KCD','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(964,35,'TRB-WQ64KS','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(965,35,'TRB-CUMXH3','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(966,35,'TRB-RLXJSL','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(967,35,'TRB-RW3EJX','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(968,35,'TRB-WJUBXJ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(969,35,'TRB-69SRBH','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(970,35,'TRB-5HSKUH','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(971,35,'TRB-7T3684','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(972,35,'TRB-VEHT76','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(973,35,'TRB-YPJURR','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(974,35,'TRB-79YVYT','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(975,35,'TRB-RW9VE9','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(976,35,'TRB-7QQAQV','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(977,35,'TRB-E9RTAX','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(978,35,'TRB-J48JX7','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(979,35,'TRB-3EW3QW','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(980,35,'TRB-WJQT2X','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(981,35,'TRB-Q382NL','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(982,35,'TRB-Y3X33X','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(983,35,'TRB-8CJTHP','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(984,35,'TRB-L3PVT4','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(985,35,'TRB-ZD78CN','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(986,35,'TRB-593ALJ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(987,35,'TRB-QCJGDU','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(988,35,'TRB-DLX8RH','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(989,35,'TRB-7TT8Q2','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(990,35,'TRB-T3AQCZ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(991,35,'TRB-XQ43HT','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(992,35,'TRB-V5JE4K','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(993,35,'TRB-WY2H69','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(994,35,'TRB-EQJUG2','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(995,35,'TRB-PRYGP9','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(996,35,'TRB-3UUYXZ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(997,35,'TRB-7QE9ZB','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(998,35,'TRB-QL7GFG','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(999,35,'TRB-2ECBZM','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1000,35,'TRB-BVKDXC','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1001,35,'TRB-TQ374D','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1002,35,'TRB-L64T7Y','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1003,35,'TRB-JSUEP8','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1004,35,'TRB-BP9XKQ','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1005,35,'TRB-WD7TK9','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1006,35,'TRB-PD56QR','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1007,35,'TRB-UZENC9','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1008,35,'TRB-TTU6JE','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1009,35,'TRB-WYCZ9P','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1010,35,'TRB-6ERGJF','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1011,35,'TRB-Y27F3E','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1012,35,'TRB-EPN3X5','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1013,35,'TRB-ENSNZW','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1014,35,'TRB-72G9YU','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1015,35,'TRB-HBBX3H','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1016,35,'TRB-RMHFE7','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1017,35,'TRB-2A4B8B','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1018,35,'TRB-P29BCG','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1019,35,'TRB-6PKGYK','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1020,35,'TRB-YMEKVB','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1021,35,'TRB-JSUXXH','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1022,35,'TRB-EL32S2','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1023,35,'TRB-ZGUXEV','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1024,35,'TRB-2N9BWP','instalado','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1025,35,'TRB-J3GVT5','instalado','[]','2026-05-21 02:26:49',4,'ninguno',0),(1026,35,'TRB-NXW42U','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1027,35,'TRB-UKBUCL','disponible','[]','2026-05-21 02:26:49',1,'ninguno',0),(1028,35,'TRB-TRNAPD','disponible','[]','2026-05-21 02:26:49',4,'ninguno',0),(1029,35,'TRB-MFG5KU','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1030,35,'TRB-X8KV98','en_transito','[]','2026-05-21 02:26:49',1,'en_transito',0),(1031,35,'TRB-8UXQT2','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1032,35,'TRB-5DKQKL','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1033,35,'TRB-6WQ2DB','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1034,35,'TRB-68NR6G','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1035,35,'TRB-XCTVRR','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1036,35,'TRB-R3NHKA','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1037,35,'TRB-89DJEC','disponible','[]','2026-05-21 02:26:49',1,'ninguno',1),(1038,35,'TRB-7DSPD4','disponible','[]','2026-05-21 02:26:49',NULL,'ninguno',0),(1189,28,'TRB-S3Q3QA','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1190,28,'TRB-S7BSHX','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1191,28,'TRB-TRD6C6','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1192,28,'TRB-UFKWV6','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1193,28,'TRB-U33EZB','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1194,28,'TRB-VW9XHP','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1195,28,'TRB-DZ39WS','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1196,28,'TRB-KP959G','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1197,28,'TRB-KAX6UY','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1198,28,'TRB-CDL3GY','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1199,28,'TRB-EPL26E','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1200,28,'TRB-A6D9L8','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1201,28,'TRB-JKBMQB','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1202,28,'TRB-5DHZ28','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1203,28,'TRB-5Q2NRD','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1204,28,'TRB-ZKZSK6','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1205,28,'TRB-6X9CWF','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1206,28,'TRB-JRRJA7','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1207,28,'TRB-G4JRRZ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1208,28,'TRB-WYP62C','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1209,28,'TRB-FVCMAZ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1210,28,'TRB-WUEXHX','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1211,28,'TRB-RN7XKB','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1212,28,'TRB-33AUTZ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1213,28,'TRB-7A4G3N','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1214,28,'TRB-7UDLTE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1215,28,'TRB-XPZGMG','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1216,28,'TRB-KJSNT3','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1217,28,'TRB-8FCTER','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1218,28,'TRB-SHXAB8','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1219,28,'TRB-3CYKQF','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1220,28,'TRB-5ET6FR','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1221,28,'TRB-8S6NRD','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1222,28,'TRB-3C27MH','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1223,28,'TRB-C2JLW8','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1224,28,'TRB-8FV8AK','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1225,28,'TRB-J5UBH7','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1226,28,'TRB-2C5NFM','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1227,28,'TRB-6HFTU4','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1228,28,'TRB-GKENZ2','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1229,28,'TRB-CPFN37','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1230,28,'TRB-PYBTXE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1231,28,'TRB-QN27GX','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1232,28,'TRB-GCQTND','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1233,28,'TRB-YN2BNB','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1234,28,'TRB-ZNMUDL','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1235,28,'TRB-3E52JQ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1236,28,'TRB-5BMYHE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1237,28,'TRB-ENVE23','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1238,28,'TRB-KW2X5K','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1239,28,'TRB-M3DVGL','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1240,28,'TRB-H5ZB6C','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1241,28,'TRB-9QFD9L','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1242,28,'TRB-ET6EKV','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1243,28,'TRB-64APLN','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1244,28,'TRB-5XAFMJ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1245,28,'TRB-PPG44S','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1246,28,'TRB-5UDZN9','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1247,28,'TRB-58WGR6','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1248,28,'TRB-94GLP7','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1249,28,'TRB-JV8KL2','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1250,28,'TRB-9QZHMY','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1251,28,'TRB-CMU9BF','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1252,28,'TRB-HDFKSK','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1253,28,'TRB-MCMMHQ','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1254,28,'TRB-D37HMC','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1255,28,'TRB-BFN8HT','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1256,28,'TRB-SWPEDL','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1257,28,'TRB-VW4WE5','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1258,28,'TRB-E2NFR5','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1259,28,'TRB-34MLBL','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1260,28,'TRB-YFFUCC','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1261,28,'TRB-E2J9LE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1262,28,'TRB-BFHKE5','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1263,28,'TRB-SMMZ8Z','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1264,28,'TRB-NT9QQE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1265,28,'TRB-YCYVMA','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1266,28,'TRB-7YFVHK','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1267,28,'TRB-WEX7KL','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1268,28,'TRB-RPF6TN','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1269,28,'TRB-QRPZQT','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1270,28,'TRB-5QMNQK','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1271,28,'TRB-LXZ3JY','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1272,28,'TRB-78VZYX','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1273,28,'TRB-HHS77X','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1274,28,'TRB-E77WC4','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1275,28,'TRB-2HN8EE','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1276,28,'TRB-TSGNYM','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1277,28,'TRB-WU6FL4','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0),(1278,28,'TRB-LD6RN9','disponible','{\"SN\":\"\",\"IP\":\"\",\"Modelo\":\"\",\"Color\":\"\",\"Ubicaci\\u00f3n\":\"\",\"Talla 41\":\"\",\"Talla 42\":\"\",\"Talla 43\":\"\"}','2026-05-21 23:03:01',NULL,'ninguno',0);
/*!40000 ALTER TABLE `inventory_skus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_user_stock`
--

DROP TABLE IF EXISTS `inventory_user_stock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_user_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` decimal(10,2) DEFAULT 0.00,
  `is_epp` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_product` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_user_stock_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_user_stock_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_user_stock`
--

LOCK TABLES `inventory_user_stock` WRITE;
/*!40000 ALTER TABLE `inventory_user_stock` DISABLE KEYS */;
INSERT INTO `inventory_user_stock` VALUES (2,1,29,200.00,0),(4,6,29,200.00,0),(5,1,31,52.00,0),(7,5,32,10.00,0),(8,1,32,4.00,0),(9,1,37,1.00,0);
/*!40000 ALTER TABLE `inventory_user_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_permissions`
--

DROP TABLE IF EXISTS `role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `module` varchar(100) NOT NULL,
  `can_view` tinyint(1) DEFAULT 1,
  `can_create` tinyint(1) DEFAULT 0,
  `can_edit` tinyint(1) DEFAULT 0,
  `can_delete` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_permissions`
--

LOCK TABLES `role_permissions` WRITE;
/*!40000 ALTER TABLE `role_permissions` DISABLE KEYS */;
INSERT INTO `role_permissions` VALUES (1,4,'dashboard',1,0,0,0),(2,4,'actas',1,0,0,0),(5,1,'dashboard',1,0,0,0),(6,1,'actas',1,0,0,0),(7,1,'settings',1,0,0,0),(10,3,'dashboard',1,0,0,0),(11,3,'actas',1,0,0,0),(12,3,'inventario',1,0,0,0),(13,5,'dashboard',1,0,0,0),(14,2,'dashboard',1,0,0,0),(15,2,'actas',1,0,0,0),(16,2,'soporte',1,0,0,0);
/*!40000 ALTER TABLE `role_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Gerente',''),(2,'Tecnico',''),(3,'Almacen',''),(4,'Administración',''),(5,'Cliente','Acceso limitado para clientes');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES ('app_name','Turbo Perú'),('bg_color','#ffffff'),('contact_email','contacto@turbosaas.com'),('currency','PEN'),('date_format','Y-m-d'),('favicon','uploads/settings/favicon_1778558219.png'),('global_notification_banner','fsdfsdfsdfsfsfd'),('global_notification_push','0'),('hover_effect','shadow'),('logo_dark','uploads/settings/logo_dark_1778600385.png'),('logo_light','uploads/settings/logo_light_1778534066.png'),('logo_pwa','uploads/settings/logo_pwa_1778558219.png'),('maintenance_mode','0'),('phone_main',''),('phone_secondary',''),('primary_color_dark','#f07d00'),('primary_color_light','#0e4194'),('ruc',''),('slogan',''),('social_links','{}'),('text_color','#333333'),('toast_position','bottom-right'),('toast_style','card'),('typography','Outfit'),('website',''),('work_hours','');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_attachments`
--

DROP TABLE IF EXISTS `ticket_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message_id` int(11) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `message_id` (`message_id`),
  CONSTRAINT `ticket_attachments_ibfk_1` FOREIGN KEY (`message_id`) REFERENCES `ticket_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_attachments`
--

LOCK TABLES `ticket_attachments` WRITE;
/*!40000 ALTER TABLE `ticket_attachments` DISABLE KEYS */;
INSERT INTO `ticket_attachments` VALUES (1,10,'uploads/tickets/tkt_3_1778640132_6a03e50423b20.jpeg','quiero_que_lo_reimagines_en_202605111733.jpeg','2026-05-13 02:42:12'),(2,20,'uploads/tickets/tkt_4_1778641038_6a03e88e0c223.webp','1776657055_fb7a021e-b4e1-42d6-bfc4-77bceeb99e70.webp','2026-05-13 02:57:18'),(3,22,'uploads/tickets/tkt_4_1778641053_6a03e89d38ea7.webp','1776656952_IMG_1499.webp','2026-05-13 02:57:33'),(4,25,'uploads/tickets/tkt_4_1778641096_6a03e8c8b4706.webm','audio_record.webm','2026-05-13 02:58:16'),(5,26,'uploads/tickets/tkt_4_1778641118_6a03e8de25967.webm','audio_record.webm','2026-05-13 02:58:38'),(6,47,'uploads/tickets/tkt_10_1778713768_6a0504a86acca.webp','reemplaza_con_la_imagen_de_202605032155.webp','2026-05-13 23:09:28');
/*!40000 ALTER TABLE `ticket_attachments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_categories`
--

DROP TABLE IF EXISTS `ticket_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#3b82f6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_categories`
--

LOCK TABLES `ticket_categories` WRITE;
/*!40000 ALTER TABLE `ticket_categories` DISABLE KEYS */;
INSERT INTO `ticket_categories` VALUES (1,'AVERIA','#3b82f6','2026-05-13 01:56:11'),(2,'MANTENIMIENTO','#6714ff','2026-05-13 01:56:24');
/*!40000 ALTER TABLE `ticket_categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_messages`
--

DROP TABLE IF EXISTS `ticket_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `is_system_message` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ticket_id` (`ticket_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `ticket_messages_ibfk_1` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ticket_messages_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_messages`
--

LOCK TABLES `ticket_messages` WRITE;
/*!40000 ALTER TABLE `ticket_messages` DISABLE KEYS */;
INSERT INTO `ticket_messages` VALUES (1,1,1,'sfsdf',0,'2026-05-13 01:53:45',0),(2,1,1,'fsdfsdf',0,'2026-05-13 01:55:29',0),(3,2,NULL,'sdgfsdg',0,'2026-05-13 02:11:32',1),(4,2,1,'hace cuanto tiempo',0,'2026-05-13 02:11:53',1),(5,2,6,'hace media hora',0,'2026-05-13 02:24:50',1),(6,2,1,'proceso a programar',0,'2026-05-13 02:25:03',1),(7,3,NULL,'sdgsdg',0,'2026-05-13 02:29:00',1),(8,3,1,'hola',0,'2026-05-13 02:29:15',1),(9,3,NULL,'se me fue el internet',0,'2026-05-13 02:29:27',1),(10,3,NULL,'',0,'2026-05-13 02:42:12',1),(11,3,1,'[LOCATION:-11.85,-77.06]',0,'2026-05-13 02:45:06',1),(12,3,NULL,'asdasdasd',0,'2026-05-13 02:47:32',1),(13,3,1,'asdasdasd',0,'2026-05-13 02:47:53',1),(14,3,NULL,'[LOCATION:-11.85,-77.06]',0,'2026-05-13 02:50:07',1),(15,4,NULL,'sgfsdfg',0,'2026-05-13 02:56:14',1),(16,4,NULL,'El ticket ha sido asignado a: Javier Mendoza',1,'2026-05-13 02:56:39',1),(17,4,NULL,'El estado del ticket ha cambiado a: EN_PROCESO',1,'2026-05-13 02:56:50',1),(18,4,NULL,'holasdasf',0,'2026-05-13 02:57:04',1),(19,4,1,'fdsgdf',0,'2026-05-13 02:57:10',1),(20,4,1,'',0,'2026-05-13 02:57:18',1),(21,4,1,'[LOCATION:-11.85,-77.06]',0,'2026-05-13 02:57:24',1),(22,4,NULL,'',0,'2026-05-13 02:57:33',1),(23,4,NULL,'[LOCATION:-11.85,-77.06]',0,'2026-05-13 02:57:39',1),(24,3,NULL,'El estado del ticket ha cambiado a: EN_PROCESO',1,'2026-05-13 02:57:55',1),(25,4,1,'',0,'2026-05-13 02:58:16',1),(26,4,NULL,'',0,'2026-05-13 02:58:38',1),(27,4,NULL,'El estado del ticket ha cambiado a: TERMINADO',1,'2026-05-13 02:59:02',1),(28,4,NULL,'El estado del ticket ha cambiado a: EN_PROCESO',1,'2026-05-13 03:11:10',1),(29,4,NULL,'El estado del ticket ha cambiado a: TERMINADO',1,'2026-05-13 03:11:19',1),(30,3,NULL,'El ticket ha sido asignado a: Ronald',1,'2026-05-13 03:38:17',1),(31,3,1,'dfdf',0,'2026-05-13 03:43:54',1),(32,3,4,'dfdfdf',0,'2026-05-13 03:44:01',1),(41,9,NULL,'SDGSDGH',0,'2026-05-13 19:35:20',1),(42,9,NULL,'El estado del ticket ha cambiado a: EN_PROCESO',1,'2026-05-13 19:35:32',1),(43,9,NULL,'El ticket ha sido asignado a: Admin',1,'2026-05-13 19:35:36',1),(44,10,1,'sd',0,'2026-05-13 23:09:08',0),(45,10,NULL,'El estado del ticket ha cambiado a: EN_PROCESO',1,'2026-05-13 23:09:11',1),(46,10,1,'sdfsdgsdg',0,'2026-05-13 23:09:15',0),(47,10,1,'sdf',0,'2026-05-13 23:09:28',0),(48,10,1,'[LOCATION:-11.85,-77.06]',0,'2026-05-13 23:09:33',0);
/*!40000 ALTER TABLE `ticket_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ticket_priorities`
--

DROP TABLE IF EXISTS `ticket_priorities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ticket_priorities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `color` varchar(20) DEFAULT '#eab308',
  `level` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ticket_priorities`
--

LOCK TABLES `ticket_priorities` WRITE;
/*!40000 ALTER TABLE `ticket_priorities` DISABLE KEYS */;
INSERT INTO `ticket_priorities` VALUES (2,'Alta','#fe011b',1,'2026-05-13 02:23:49'),(3,'Media','#ff9500',1,'2026-05-13 02:24:00'),(4,'Baja','#082ee7',1,'2026-05-13 02:24:19');
/*!40000 ALTER TABLE `ticket_priorities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tickets`
--

DROP TABLE IF EXISTS `tickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cliente_id` int(11) DEFAULT NULL,
  `asunto` varchar(255) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `prioridad_id` int(11) DEFAULT NULL,
  `assigned_to` int(11) DEFAULT NULL,
  `estado` enum('nuevo','pendiente','en_proceso','terminado','eliminado') DEFAULT 'nuevo',
  `descripcion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `public_token` varchar(64) DEFAULT NULL,
  `active_tech_id` int(11) DEFAULT NULL,
  `active_tech_ping` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cliente_id` (`cliente_id`),
  KEY `categoria_id` (`categoria_id`),
  KEY `prioridad_id` (`prioridad_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `tickets_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tickets_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `ticket_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_3` FOREIGN KEY (`prioridad_id`) REFERENCES `ticket_priorities` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tickets_ibfk_4` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tickets`
--

LOCK TABLES `tickets` WRITE;
/*!40000 ALTER TABLE `tickets` DISABLE KEYS */;
INSERT INTO `tickets` VALUES (1,3,'Se malogro el internet',NULL,NULL,NULL,'eliminado','sfsdf','2026-05-13 01:53:45','2026-05-13 19:28:12',NULL,NULL,NULL),(2,3,'se me fue el internet',1,NULL,3,'eliminado','sdgfsdg','2026-05-13 02:11:32','2026-05-13 19:28:16','43f65277498ea83a1746980664bd2b12',NULL,NULL),(3,2,'Se me fue el internet ',1,4,4,'eliminado','sdgsdg','2026-05-13 02:29:00','2026-05-13 19:28:15','5b0ffe2cafac27116b65755c40752183',NULL,NULL),(4,2,'ticket test 00005965',2,4,5,'eliminado','sgfsdfg','2026-05-13 02:56:14','2026-05-13 19:28:08','91c476266aab95373fc4daf51114e51c',NULL,NULL),(9,3,'Se malogro el internet',1,2,1,'eliminado','SDGSDGH','2026-05-13 19:35:20','2026-05-13 23:25:12','1391991f51c5edf895d4c49627f7c373',NULL,NULL),(10,2,'Se malogro el internet',1,2,1,'eliminado','sd','2026-05-13 23:09:08','2026-05-13 23:09:50',NULL,NULL,NULL);
/*!40000 ALTER TABLE `tickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `pin` varchar(10) DEFAULT NULL,
  `whatsapp` varchar(50) DEFAULT NULL,
  `profile_picture` varchar(255) DEFAULT NULL,
  `cover_picture` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `pin` (`pin`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@admin.com','admin@turbosaas.com','$2y$10$taTnrJmWuABFcs/uDblnXOM1dX9X7ucPs/Pkdo/46C82Up0eDiGVC','admin','2026-05-11 20:00:24','4444','','uploads/profiles/prof_1_6a0fd01c9f281.png','uploads/profiles/cov_1_6a0fccdb0eb61.jpeg'),(3,'Cesar Alexander Mendoza Castro',NULL,'cesarestudio2395@gmail.com','$2y$10$S0uTJp/G.IGhvZ.i3pNUSezj2/liQKfTw5RSwH4nW9hRCwcQfcSji','Gerente','2026-05-11 21:12:04','2308',NULL,NULL,NULL),(4,'Ronald',NULL,'ronald123@gmail.com','$2y$10$a0S5LbpaJY6UIm6I6VrkJu4rBxOfKf5eQUoYEobk8PPzsQHjd9izO','Tecnico','2026-05-12 16:45:31','1234','','uploads/profiles/user_4_1778615410.jpg',NULL),(5,'Javier Mendoza',NULL,'74589633566@cliente.turbosaas.com','$2y$10$jL7X9Ng43gjGHJ6QVRlY/uNuGTolxHlt/ZvowJyW3Fd1x.qtmUKWO','Cliente','2026-05-13 00:57:01','123456','95621450000',NULL,NULL),(6,'MENDOZA CASTRO CESAR ALEXANDER',NULL,'10742146362@cliente.turbosaas.com','$2y$10$k1IMxdQtk0s7.pYvvw0kl.QMFnkWkKO3yEX2ZzYt1nixx9gip9jlK','Cliente','2026-05-13 01:25:52','082420','95621450000',NULL,NULL),(8,'Test Cronometro',NULL,'12345678@cliente.turbosaas.com','$2y$10$m05/ISIeAdU9VZNqEZhbzu4Vu41KoLuAS79BXv5QC.WL9.4xJ8Pb6','Cliente','2026-05-16 17:29:57','254395','',NULL,NULL),(9,'Luis Lopez',NULL,'121565656565656565656565656123@cliente.turbosaas.com','$2y$10$fVXdsr./C4WLSH3DBb10c.D5fYx9gmpouWqtg53OT3VxRYigeutzS','Cliente','2026-05-16 18:01:00','537710','',NULL,NULL),(11,'Luis Lopez',NULL,'c_1778995826_121565656565656565656565656123@cliente.turbosaas.com','$2y$10$JHeD7PeNwJwkatFaRwpVv.BaHCTsORn8G.6B3ILMa83yTksI6.g8e','Cliente','2026-05-17 05:30:26','890772','',NULL,NULL),(13,'Luis Lopez',NULL,'c_1778995841_121565656565656565656565656123@cliente.turbosaas.com','$2y$10$Db.bAczDOucxgshkO0jNm.dzuuMwf3sEfiIPUFJHqS0U12iaeens.','Cliente','2026-05-17 05:30:41','220288','',NULL,NULL);
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-22  3:10:20


-- Cambios para el Perfil de Usuario
ALTER TABLE users ADD COLUMN IF NOT EXISTS username VARCHAR(50) DEFAULT NULL AFTER id;
ALTER TABLE users ADD COLUMN IF NOT EXISTS cover_picture VARCHAR(255) DEFAULT NULL AFTER profile_picture;

-- Cambios para las Actas
ALTER TABLE actas ADD COLUMN IF NOT EXISTS cliente_rotulado TINYINT(1) DEFAULT 0 AFTER cliente_whatsapp;

-- Cambios para Inventario y Mochila (EPP)
ALTER TABLE inventory_skus ADD COLUMN IF NOT EXISTS is_epp TINYINT(1) DEFAULT 0 AFTER status;
ALTER TABLE inventory_user_stock ADD COLUMN IF NOT EXISTS is_epp TINYINT(1) DEFAULT 0 AFTER quantity;

-- Cambios para las Variantes de Productos
ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS product_type VARCHAR(20) DEFAULT 'simple' AFTER is_bulk;
ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS parent_product_id INT(11) DEFAULT NULL AFTER product_type;
ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_brand VARCHAR(100) DEFAULT NULL AFTER parent_product_id;
ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_size VARCHAR(50) DEFAULT NULL AFTER variant_brand;
ALTER TABLE inventory_products ADD COLUMN IF NOT EXISTS variant_attributes TEXT DEFAULT NULL AFTER variant_size;
