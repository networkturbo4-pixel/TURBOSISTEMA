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
  `servicio_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `folio` (`folio`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `actas`
--

LOCK TABLES `actas` WRITE;
/*!40000 ALTER TABLE `actas` DISABLE KEYS */;
INSERT INTO `actas` VALUES (8,'000001','LIM-','80e21d9b37e7a022b62f47a0d4655c8a','2026-05-13 00:15:23','Javier Mendoza','74589633566','fasfd','Puente Piedra','','95621450000','','','',NULL,'','','','','','2026-05-13','11:48:00','11:48:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,5,'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAwMAAACWCAYAAACCYVROAAAQAElEQVR4AezdPW9kVx3H8TtU0FHQICGx+xIiWqQ4LwEpBVRspBQ0dKHGW4MEVAiJiGwFUsRrwBEpkCigo2QRDUUkKOlCvmOO43W8a6/tmblz7wdxdp7uwzmfMw//370zzpc+9T8CBAgQIECAAAECBFYp8KXJ/wgQWJGAoRIgQIAAAQIEPhcQBj63cI0AAQIECCxLwGgIECBwg4AwcAOQhwkQIECAAAECBAgcg8Bd+igM3EXNOgQIECBAgAABAgQWICAMLGASDWGtAsZNgAABAgQIELifgDBwPz9rEyBAgACB/QjYCwECBHYgIAzsANUmCRAgQIAAAQIECNxHYF/rCgP7krYfAgQIECBAgAABAjMTEAZmNiG6s1YB4yZAgAABAgQI7F9AGNi/uT0SIECAwNoFjJ8AAQIzERAGZjIRukGAAAECBAgQILBMgTmPShiY8+zoGwECBAgQIECAAIEdCggDO8S16bUKGDcBAgQIECBA4DgEhIHjmCe9JECAAIG5CugXAQIEjlhAGDjiydN1AgQIECBAgACB/QosbW/CwNJm1HgIECBAgAABAgQI3FJAGLgllMXWKmDcBAgQIECAAIHlCggDy51bIyNAgACB1xWwPAECBFYmIAysbMINlwABAgQIECBA4FzAv9MkDHgWECBAgAABAgQIEFipgDCw0olf57CNmgABAgQIECBA4LKAMHBZw3UCBAgQWI6AkRAgQIDAjQLCwI1EFiBAgAABAgQIEJi7gP7dTUAYuJubtQgQIECAAAECBAgcvYAwcPRTuNYBGDcBAgQIECBAgMB9BYSB+wpanwABAgR2L2APBAgQILATAWFgJ6w2SoAAAQIECBAgcFcB6+1PQBjYn7U9ESBAgAABAgQIEJiVgDAwq+lYa2eMmwABAgQIECBA4BACwsAh1O2TAAECaxYwdgIECBCYjYAwMJup0BECBAgQIECAwPIEjGjeAsLAvOdH7wgQIECAAAECBAjsTEAY2BntWjds3AQIECBAgAABAsciIAwcy0zpJwECBOYooE8ECBAgcNQCwsBRT5/OEyBAgAABAgT2J2BPyxMQBpY3p0ZEgAABAgQIECBA4FYCwsCtmNa6kHETIECAAAECBAgsWUAYWPLsGhsBAgReR8CyBAgQILA6AWFgdVNuwAQIECBAgACBaWJAIAFhIAWNAAECBAgQIECAwAoFhIHVTLqBEiBAgAABAgQIEHhRQBh40cMtAgQILEPAKAgQIECAwC0EhIFbIFmEAAECBAgQIDBnAX0jcFcBYeCuctYjQIAAAQIECBAgcOQCwsBRTqBOEyBAgAABAgQIELi/gDBwf0NbIECAwG4FbJ0AAQIECOxIQBjYEazNEiBAgAABAgTuImAdAvsUEAb2qW1fBAgQIECAAAECBGYkIAwcfDJ0gAABAgQIECBAgMBhBISBw7jbKwECaxUwbgIECBAgMCMBYWBGk6ErBAgQIECAwLIEjIbA3AWEgbnPkP4RIECAAAECBAgQ2JGAMPCgsDZGgAABAgQIECBA4HgEhIHjmSs9JUBgbgL6Q4AAAQIEjlxAGDjyCdR9AgQIECBAYD8C9kJgiQLCwBJn1ZgIECBAgAABAgQI3EJAGHgpkgcIECBAgAABAgQILFtAGFj2/BodAQK3FbAcAQIECBBYoYAwsMJJN2QCBAgQILB2AeMnQOBcQBg4d/AvAQIECBAgQIAAgdUJrCQMrG5eDZgAAQIECBAgQIDAjQLCwI1EFiBA4OgEdJgAAQIECBC4lYAwcCsmCxEgQIAAAQJzFdAvAgTuLiAM3N3OmgQIECBAgAABAgSOWuAIw8BRe+s8AQIECBAgQIAAgdkICAOzmQodIUDgWgF3EiBAgAABAjsTEAZ2RmvDBAgQIECAwOsKWJ4Agf0KCAP79bY3AgRuEPjd73431W5YzMMECBAgQIDAAwgcOAw8wAhsggCBxQj89Kc/nb73ve9tm0CwmGk1EAIECBCYsYAwMOPJ0TUCixO4YUCffPLJxRI//OEPp+fPn1/cdoUAAQIEdiMw3mu7rJ2dnU1nn7WnT59Oo7377rsX1995553pahvLdVCndWu76a2tPrSAMPDQorZHgMCdBX7wgx9MX/va17brFwyePXu2ve4fAgSOU0Cv5ycwiv2K97feemv6yle+Mj1+/HjabDbby653f+309HQ6/X97//33L65/8MEH0wdX2ljuRz/60dS6tc3mfJtdH+Gh/Y51z87O5ge0wh4JAyucdEMmMFeBR48eTX/+85+nLuvj6WcfQn1wdF0jQIAAgdsLVPTXeg+tGK/I32w+L857f60Y/+9//3v7jd5hyfrQfkYAaL8jGNSvzebzPtXXziy0zh12ZZU7CjxgGLhjD6xGgACBSwIFgSdPnlzc0weHD4YLDlcIECDwgkDvj7UK6Vpf56nIrvivjffQ8d7a+2v31X7zm99Mv/3tb6e///3v0x/+8Idt6/rl1v0/+clPppOTk+ntt9+eLq/fNmrdV+vxsVyXL3T0FTfqf4GhbXVmoX5vNuchoeuNZwSILhvnCBddtu5or9iNh14iIAy8BMbdBAjcILDDh3/84x9vP3DGLvog6MNi3HZJgACBNQn0/lexWxFc6z2xInmz+bxgrpCu/f73v9/+3qrrFfKffvrpRbFf8V/rPbZWAf/d7353eza24r326NGj7e1H/7/svvfee28bFD788MPp8vpto9Z9tR5vn+Ny7Ltw0f0tU2u/tbZda1/Xzefz58+3Y2nsFf2jNbZCwWh5jNYy123LfS8XEAZebuMRAgQOKNAHxviAeP7ZB0Jv+l0esEt2TWDVAga/P4He6yr6a5vNecFfsVuhW2Hce2PFdO+TFdkV2xXetX//+9/b4r8ivUJ7f72+fk/1tVZf6nOtftfqe63+j9aZih6rtWzr1drG9Xtw730FhIH7ClqfAIGdCfRhMD4A+gD0g+KdUdswAQIHFBjFfwX/ZnNe/Ff416WOglcwV+hXMHe998aK/VEsj/fJlj/W1hhqnaloXLXG2XhrjT2DWtdH67HaWLb1jtXgUP1+SRg4VHfslwABAp8LdDTo8ht7H4odKft8CdcIECBwXAIV/hX6vZddLf57z6uwHQVvBX+t+49rlLvtbaFhtGxqfVZ0uds9L3PrwsAy59WoCLyewIyX7oOwN/nRRYFgSLgkQGDuAhX+P//5z7d/n/9y4V8QqO8Vr1eL/+7rMY3AvgSEgX1J2w8BAncW6PRvR4HGBgSCIeGSwN0ErLU7gefPn18U//3I91e/+tX2R7AV+VcL/w52dP/uemPLBG4WEAZuNrIEAQIzEOhDVCCYwUToAgECXxC4GgD6GlBFft9r/9vf/nbxF3i67wsru4PA7gVeuQdh4JU8HiRAYC4CBYHLXxeqX50h6EO46xoBAgT2KdB7T0V/X//pDEC3K/Yvf9+/96199sm+CNxFQBi4i5p1CMxZYMF965R6AeDyEPsQ/vjjjy/f5ToBAgR2InB2drb9ClDvO4WAf/zjH9uj/gWAvs7Ye9ROdmyjBHYoIAzsENemCRB4eIE+bK8ebfvOd76z/U7uw+/NFgnMX0APdyfQ0f5+7FsrAIw/b9zXFvsK0HXvR7vrjS0T2I2AMLAbV1slQGCHAn0Qf/nLX77YwyeffDJ1lK4P7os7XSFAgMAdBHofqfjvPaXWJt58883tf8hrHP2/ekCiZTQCexJ48N0IAw9OaoMECOxaoA/iX/7yly/spg/wPri7fOEBNwgQIHCDQO8bBYCO/r/zzjvbpTvoMI7+91uA7Z3+IbBAAWFggZNqSAsSMJSXCjx58mT7Xd3LC/SB/tZbb12+y3UCBAhcK9D7xQgA432j4r8Q0Nd/rl3JnQQWKCAMLHBSDYnAWgQKBKenpy8Mtw/48cH+wgNuEDgCAV3crUDvDwWAjv7X2lvFfyFAAEhDW6OAMLDGWTdmAgsS6AP8aiDoL36MD/oFDdVQCBC4o0DvCYWAcaCg7/4XAnr/6GuHd9ys1QjcV2AW6wsDs5gGnSBA4D4CfaBfDQT9/e8+/O+zXesSIHC8AuMsQL8D+Oijj6bvf//72x8B935xvKPScwIPLyAMPLypLRK4XsC9OxXog/7k5OSFfRQQnj59+sJ9bhAgsGyBQsA4GPDNb37zIgA8evRo2QM3OgJ3FBAG7ghnNQIE5iXQB32n/ru83DOB4LKG6/sUsK/9CRQACv6dBei/BdCBgd4P+l3R/nphTwSOU0AYOM5502sCBK4RKAj0PeAuLz8sEFzWcJ3AMgQKAJ0B6HcAv/jFLyZnAZYxr0c8iqPtujBwtFOn4wQIXCdQELguEFQ0dOTwunXcR4DA8QgUAvoDAYWAet3r/Wc/+9nkLEAaGoHXFxAGXt/MGgSmicGsBR49ejRVIHQ5OloBIRAMDZcEjkfg8mt3fA2o3wj150AFgOOZRz2dr4AwMN+50TMCBO4hUBB4WSDozwzeY9NWXaGAIe9foPDeGYACQGf1Ln8NqN8E7L9H9khgmQLCwDLn1agIEPhM4GWBoAJDIPgMyP8JzEyg12Wvz81mMxUAeg1/+umn278I5CzAzCZr2d1Z1eiEgVVNt8ESWJ9AxcR1ZwgqOJ4/f74+ECMmMDOBXocV/pvNZup1OV6zfQ3IfxNgZpOlO4sUEAYWOa0G9VoCFl68wCguuhyDrQDpB4hdjvtcEiCwH4FedyMA9DWgvhLUX/0aAcDXgPYzD/ZCIAFhIAWNAIHFCxQEOkNwucioIBEIFj/1XxigOw4j0OvtugAwvgbkLMBh5sVeCQgDngMECKxGoEDQf4ioI5Bj0BUoAsHQcEngYQV6fV0XADoDUBMAHtbb1q4VcOcNAsLADUAeJkBgWQIFgv4soUCwrHk1mvkIjADQ139qvdb68W9BfASAXofz6bGeEFi3gDCw7vlf3uiNiMAtBCpEOiJZkTIWr4DpDMG47ZIAgdsL9PrpDEDFf228trrsa0AFgQLB7bdoSQIE9iUgDOxL2n4IEJidQIGg3xEUDupcBU2FTJfd1uYvoIeHE+h18qoAMM4CHK6H9kyAwG0EhIHbKFmGAIHFCvSD4quBwBmCxU63gd1TYASAXiMF5478t8kuK/5rhezu0wjsQMAmdyAgDOwA1SYJEDgugc4MFAjG1xgqeCp2jmsUektgNwK9HjoD0GtiBIDuKwD0uhkBoNfRbnpgqwQI7FJAGNilrm3fT8DaBPYoUCHTEc0KnHZ7dnY2Vfh0XSOwNoGK/QJA/xGwXgenp6dTr4kCc9//HwHg5ORkbTTGS2BxAsLA4qbUgAgQuKvA1UBQQdTR0Ltuz3qvJ2DpwwtU8BcCRgDoPwbW66Iw4IfAh58fPSCwCwFhYBeqtkmAwFELdIagI58NouLojTfemAoG3dYILE2g53YBYLPZTIXfCv/LAaDXQq+JpY3beA4uoAMzERAGZjIRukGAwLwEKoYqgr761a9Of/3rNKZDOgAACIxJREFUX6dnz54JBPOaIr25h8DlAPD48ePpcgDoeV8TAO4BbFUCRyQgDBzRZB11V3WewBEKFAj+8pe/TF1WLAkERziJunwhMAJAxX+t5/R4blf81woA3XexkisECCxeQBhY/BQbIAEC9xGoMOovpnRZ8dTXKSqq7rPNNaxrjPMQ6Lnac7av/4wAUM96Lve8FgDS0AisW0AYWPf8Gz0BArcQKAhUOHXZDyorrPotwS1WtQiBgwj0PL36l4D6yz/+EtBBpmMNOzXGIxYQBo548nSdAIH9CRQECgQVVB1tLRDUCgXd3l9P7InA9QI9DzsLsNlspoJAgaDnbWcB+ktAPX/706DXr+1eAgTWKiAMrHXm7zNu6xJYqUCFVQVVR1e7XhAoEIzCq2JspTSGfSCBnnMjAIyvAfXcHAFgfA3oQN2zWwIEjkBAGDiCSdJFAgTmJdDR1YqsLiu8CgUFgv4EaYVZt+fV4/v1xtrzEhgBoOK/VuHf87DLnpe1fgg8r17rDQECcxUQBuY6M/pFgMDsBTpD0JmCt99+e9vX//znP9s/0djZgoq0Dz74YHu/fwjcV2AEgPHcqvBvm132HBwBoFDQ/RqBewhYdWUCwsDKJtxwCRB4WIGKrw8//HCqGKswG1uveOtsQaGgswXdHo+5JHBbgZ4377777tTzqOfX2dnZxZ+67TnXGYB+x3Lb7VmOAAECVwWEgasia7ttvAQIPIhAoaDCrAKtMwajQKuYOz09nTqiWzg4Ozt7kP3ZyLIFet4UIgsB77///vSNb3xje9apHwL3HOu5tmwBoyNAYF8CwsC+pO2HAIFVCBQK+i3B+OpG1xt4xV1fGyoUVOBV6HVfj+2z2de8BXqOFBp7jnT99LMgWfH/z3/+cxIA5j13ekfgWAWEgWOdOf0mQGD2AgWDzhJUzFXUOVsw+ynbewcLhLXC4WazmbqsEz1fet4UAHoedZ9G4A4CViFwo4AwcCORBQgQIHA/gYq5ijpnC+7nuJS1+6pYRf84S9SZgMY2nh8FyJ4v3acRIEBg1wLCwK6F97l9+yJAYPYCBYOKvY76dvS323W6o8PdrkCsOOwrIt2vHbdA8/rxxx9vj/gXAPr6z0cffbQdVM+DfgNQCKj4H2eOtg/6hwABAnsSEAb2BG03BAgQuCxQCKgALBRUDF79bUGBoMLx6dOnU0eSL687rruch0AFf615ar4KdM1dl83jn/70p21H33zzze1fnWreaz0Htg/4hwABAgcUEAYOiG/XBAgQSODk5GTqKHHBoMtud38F5jhbUHFZodl9PaYdRiD/zto0FxX6FfzNTa3bPd78FfBGe++996aK/+4/TK/tdSEChkFgJwLCwE5YbZQAAQKvL9CR4idPnkwVkQWDgkD3taWKzG5XfNYqSLtfe3iBrGsV/LW8N5vNtNlstn/vv6I//+bm5LMg13z1dZ/mrDBX4d9jD98zWyRAgMDDCwgDD2/6MFu0FQIEVi1QMVlRWaFZKyQEUpHa11EqSDsaXaFaYdpj2usJDMsK/lqWm815wZ9t4WvYdr3WXIzCv/mpFQheb8+WJkCAwHwEhIH5zIWeECCwYoGXDb1QULHZEedx5FkweJnW9fe/qugvAFTk11ou767XKvwz77Kiv9ZcXL8X9xIgQOA4BYSB45w3vSZAYIUCFaoFgREMKlIrWitQK2SvnjHoaHf3rYFqjL8x1yryO7q/2Zwf6e92VrVMssyt2zlW9I9W0V/r8TXYGeNBBOyUwGwEhIHZTIWOECBA4PYCo5itaB3FbCGhAnYUxhW6FcEVxRXIFcG338P8lhzjaiy1virV+Dab6wv+ls+plkUtq/E1n67nl1nLzG/EekSAAIHdCwgDuzeeJvsgQIDAjgUqZjtrUIHbEe4uu12hW1FcIVzhXDCoiJ5jMKiftfpWsV8bfd5svljw933+lo228TfWxllr/KPoz6Oiv9YyLa8RIECAwLmAMHDu4F8CBAg8mMChNzQK484UVBRXDHe9cFDfKqJHkb3vYHBdsV9fNpvzYr+w0u0K+lrFfuvU78ZVq6DvsVrjG0V/1yv4ay3TOhoBAgQIvFpAGHi1j0cJECBw9AIV0AWBAkHBoNb1CuarwaDbdx1wRXutAr7tdGS/sFFxX5G/2ZwX/N2ukK+1bG3ss77Wr/rb4/WzIn8U/PW92xX8tZYd67okcCABuyVw1ALCwFFPn84TIEDg9QUquCu2K7RHcd3tCvmK9wr3N954Y6qgr3X/y/bSYxX9X//617d/g//x48dTxX7bqZhv/Yr9lmsb7bv25MmTqcdrFff1YxT83a5vFfstd3Jy0qoaAQIECOxAQBh4XVTLEyBAYEECFeYV2xXeFeEV5V3/1re+NT179myqqK/AH61Cf7TN5vxIfwX9v/71r61K26tVxHd/bWx3FPvtYxT77av9t852A/4hQIAAgb0KCAN75bYzAgSOTWBt/a0or5D/9a9/PVXEjwJ+FO89PkxarmK/9sc//nEayyr2h5BLAgQIzF9AGJj/HOkhAQIEDipQAOjofcV/oaCQUOt6R/Zr3/72tw/aRzsn8EACNkNgdQLCwOqm3IAJECBAgAABAgQInAusOwycG/iXAAECBAgQIECAwCoFhIFVTrtBE1ingFETIECAAAECLwoIAy96uEWAAAECBAgsQ8AoCBC4hYAwcAskixAgQIAAAQIECBBYosBywsASZ8eYCBAgQIAAAQIECOxQQBjYIa5NEyCwOwFbJkCAAAECBO4vIAzc39AWCBAgQIAAgd0K2DoBAjsSEAZ2BGuzBAgQIECAAAECBOYuMM8wMHc1/SNAgAABAgQIECCwAAFhYAGTaAgEjl1A/wkQIECAAIHDCAgDh3G3VwIECBAgsFYB4yZAYEYCwsCMJkNXCBAgQIAAAQIECOxT4H8AAAD//0nhGqAAAAAGSURBVAMATrquTT2Jk+wAAAAASUVORK5CYII=','data:image/png;base64,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',NULL,NULL),(9,'000009','LIM-','554e0c958202e4a881eb6d982a442715','2026-05-16 17:29:57','Test Cronometro','12345678','Calle Test 123','','','','','','',NULL,'','','','','','2026-05-16','12:29:00','00:00:00','Instalación','Finalizada (Éxito)',4,'','','','',0,'','','','',0,0,'','','',NULL),(10,'000010','LIM-','b0a00f26b354f34718880ea69a514c49','2026-05-16 18:01:00','Luis Lopez','121565656565656565656565656123','','','','','','','',NULL,'','','','','','2026-05-16','00:30:00','00:00:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,0,'','','',NULL),(11,'000011','LIM-','166e28f7a9bf2a4b2d828b9e0691a320','2026-05-16 20:40:49','Javier Mendoza','','','','','','','','',NULL,'','','','','','2026-05-16','00:33:00','00:00:00','Instalación','Finalizada (Éxito)',1,'','','','',0,'','','','',0,0,'','','',NULL);
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
-- Table structure for table `attendance_logs`
--

DROP TABLE IF EXISTS `attendance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `attendance_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `type` enum('entrada','salida') NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `attendance_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance_logs`
--

LOCK TABLES `attendance_logs` WRITE;
/*!40000 ALTER TABLE `attendance_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance_logs` ENABLE KEYS */;
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
  `servicio_id` int(11) DEFAULT NULL,
  `latitud` varchar(50) DEFAULT NULL,
  `longitud` varchar(50) DEFAULT NULL,
  `router_os` varchar(50) DEFAULT 'mock',
  `router_ip` varchar(100) DEFAULT NULL,
  `router_port` varchar(20) DEFAULT NULL,
  `router_user` varchar(100) DEFAULT NULL,
  `router_pass` varchar(255) DEFAULT NULL,
  `router_mac_or_id` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clientes`
--

LOCK TABLES `clientes` WRITE;
/*!40000 ALTER TABLE `clientes` DISABLE KEYS */;
INSERT INTO `clientes` VALUES (1,'Miguel Rivera Lopez','7895412023','987456321','miguelad@gmail.com','afdagf','agag','','2026-05-12 19:03:00','2026-05-31 19:03:00','2026-05-13 00:03:26',NULL,NULL,NULL,NULL,'mock',NULL,NULL,NULL,NULL,NULL),(2,'Javier Mendoza','74589633566','95621450000','','fasfd','','Plan Full - 700 Mbps ','2026-05-13 11:48:00','2026-05-13 11:48:00','2026-05-13 00:15:23',5,NULL,NULL,NULL,'mock',NULL,NULL,NULL,NULL,NULL),(3,'MENDOZA CASTRO CESAR ALEXANDER','10742146362','95621450000','','AV. LA GRAMA MZA. 01 LOTE. 1 LOS BALCONES','',NULL,NULL,NULL,'2026-05-13 01:25:52',6,1,'-12.046374','-77.042793','mock',NULL,NULL,NULL,NULL,NULL),(4,'Test Cronometro','12345678','',NULL,'Calle Test 123','','','2026-05-16 12:29:00','2026-05-16 12:29:00','2026-05-16 17:29:57',8,NULL,NULL,NULL,'mock',NULL,NULL,NULL,NULL,NULL),(5,'Luis Lopez','12156565656565656565','',NULL,'','','','2026-05-16 00:00:00','2026-05-16 00:00:00','2026-05-16 18:01:00',9,NULL,NULL,NULL,'mock',NULL,NULL,NULL,NULL,NULL),(6,'Luis Lopez','12156565656565656565','',NULL,'','','','2026-05-16 00:30:00','2026-05-16 00:30:00','2026-05-17 05:30:26',11,NULL,NULL,NULL,'mock',NULL,NULL,NULL,NULL,NULL),(7,'Luis Lopez','12156565656565656565','','','','',NULL,'2026-05-16 00:30:00','2026-05-16 00:30:00','2026-05-17 05:30:41',13,1,'-12.046374','-77.042793','mock',NULL,NULL,NULL,NULL,NULL);
/*!40000 ALTER TABLE `clientes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_assignment_log`
--

DROP TABLE IF EXISTS `inventory_assignment_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_assignment_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sku_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `sku_code` varchar(50) DEFAULT NULL,
  `product_name` varchar(255) DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_to_name` varchar(255) DEFAULT NULL,
  `assigned_by` int(11) DEFAULT NULL,
  `assigned_by_name` varchar(255) DEFAULT NULL,
  `quantity` decimal(10,2) DEFAULT 1.00,
  `is_epp` tinyint(1) DEFAULT 0,
  `action` enum('assign','unassign') DEFAULT 'assign',
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_assignment_log`
--

LOCK TABLES `inventory_assignment_log` WRITE;
/*!40000 ALTER TABLE `inventory_assignment_log` DISABLE KEYS */;
INSERT INTO `inventory_assignment_log` VALUES (1,1024,35,'TRB-2N9BWP','Producto a Granel Test',3,'Cesar Alexander Mendoza Castro',1,'Admin',1.00,0,'assign',NULL,'2026-05-25 17:27:43'),(2,1318,34,'TRB-CHZNW2','Producto Simple 0002 (Edited)',1,'Admin',1,'Admin',1.00,0,'assign',NULL,'2026-05-25 18:12:14'),(3,1318,34,'TRB-CHZNW2','Producto Simple 0002 (Edited)',15,'Pedro Lopez',1,'Admin',1.00,0,'assign',NULL,'2026-05-25 18:18:31'),(4,NULL,48,'GRANEL','Lapiceros',1,'Admin',1,'Admin',25.00,0,'assign',NULL,'2026-05-25 23:04:49'),(5,932,34,'TRB-87QKL5','Producto Simple 0002 (Edited)',3,'Cesar Alexander Mendoza Castro',1,'Admin',1.00,0,'unassign',NULL,'2026-05-26 12:16:02'),(6,932,34,'TRB-87QKL5','Producto Simple 0002 (Edited)',0,'',1,'Admin',1.00,0,'unassign',NULL,'2026-05-26 12:16:15'),(7,1316,34,'TRB-S4D4ZC','Producto Simple 0002 (Edited)',1,'Admin',1,'Admin',1.00,0,'assign',NULL,'2026-05-26 12:36:24'),(8,920,33,'TRB-FS84YV','Router TP-Link AC1200',1,'Admin',1,'Admin',1.00,0,'assign','','2026-05-26 12:43:00'),(9,NULL,54,'GRANEL','Ejempolo 02',1,'Admin',1,'Admin',10.00,0,'assign',NULL,'2026-05-26 13:08:39'),(10,NULL,56,'GRANEL','Mouse Gamer',1,'Admin',1,'Admin',50.00,0,'assign','','2026-05-24 13:16:00'),(11,1777,61,'TRB-PZEUE5','ONU XTE 2026',1,'Admin',1,'Admin',1.00,0,'assign',NULL,'2026-06-02 00:09:08');
/*!40000 ALTER TABLE `inventory_assignment_log` ENABLE KEYS */;
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
  `tipo` varchar(50) DEFAULT NULL,
  `notas` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sku_id` (`sku_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `inventory_entries_ibfk_1` FOREIGN KEY (`sku_id`) REFERENCES `inventory_skus` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_entries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_entries`
--

LOCK TABLES `inventory_entries` WRITE;
/*!40000 ALTER TABLE `inventory_entries` DISABLE KEYS */;
INSERT INTO `inventory_entries` VALUES (49,1777,1,'entrada','','2026-06-02 05:09:10'),(50,1777,1,'entrada','','2026-06-02 05:10:43'),(51,1777,1,'reparado','Cambio de estado desde selector','2026-06-02 05:10:52'),(52,1777,1,'observacion','Cambio de estado desde selector','2026-06-02 05:14:20'),(53,1777,1,'entrada','','2026-06-02 05:14:24'),(54,1777,1,'observacion','Cambio de estado desde selector','2026-06-02 05:18:53');
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
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_entry_photos`
--

LOCK TABLES `inventory_entry_photos` WRITE;
/*!40000 ALTER TABLE `inventory_entry_photos` DISABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_product_photos`
--

LOCK TABLES `inventory_product_photos` WRITE;
/*!40000 ALTER TABLE `inventory_product_photos` DISABLE KEYS */;
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
  `bulk_custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_bulk` tinyint(1) DEFAULT 0,
  `unit_type` varchar(50) DEFAULT 'Unidades',
  `requires_photos` tinyint(1) DEFAULT 0,
  `product_image` varchar(255) DEFAULT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `master_sku` (`master_sku`),
  KEY `category_id` (`category_id`),
  KEY `idx_parent_product_id` (`parent_product_id`),
  CONSTRAINT `fk_parent_product` FOREIGN KEY (`parent_product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inventory_products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `inventory_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=62 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_products`
--

LOCK TABLES `inventory_products` WRITE;
/*!40000 ALTER TABLE `inventory_products` DISABLE KEYS */;
INSERT INTO `inventory_products` VALUES (61,NULL,'normal',NULL,NULL,NULL,NULL,'ONU XTE 2026','',1,50,10,3,'[{\"name\":\"MAC\",\"type\":\"text\"},{\"name\":\"SN\",\"type\":\"text\"},{\"name\":\"IP\",\"type\":\"text\"}]',NULL,'2026-06-02 04:09:33',0,'Unidades',0,NULL,0);
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
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_sku_photos`
--

LOCK TABLES `inventory_sku_photos` WRITE;
/*!40000 ALTER TABLE `inventory_sku_photos` DISABLE KEYS */;
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
  `status` enum('disponible','instalado','malogrado','reparado','en_transito','observacion') DEFAULT 'disponible',
  `custom_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_data`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_to` int(11) DEFAULT NULL,
  `historia` enum('ninguno','devuelto','malogrado','antiguo','en_transito','observacion') DEFAULT 'ninguno',
  `is_epp` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_printed` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sku_code` (`sku_code`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `inventory_skus_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `inventory_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1787 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_skus`
--

LOCK TABLES `inventory_skus` WRITE;
/*!40000 ALTER TABLE `inventory_skus` DISABLE KEYS */;
INSERT INTO `inventory_skus` VALUES (1531,61,'TRB-62M4MQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1532,61,'TRB-HPJ3BT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1533,61,'TRB-HBENRA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1534,61,'TRB-NA4KQN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1535,61,'TRB-MZGW6R','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1536,61,'TRB-D2RHAX','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1537,61,'TRB-PBE4LU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1538,61,'TRB-4Y943F','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1539,61,'TRB-YQMYNJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1540,61,'TRB-5PCVYV','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1541,61,'TRB-DRSC44','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1542,61,'TRB-G57P2V','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1543,61,'TRB-ZYZZMS','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1544,61,'TRB-ANM92W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1545,61,'TRB-BLDSWN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1546,61,'TRB-H2A9MN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1547,61,'TRB-6HN93M','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1548,61,'TRB-FZWC69','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1549,61,'TRB-UZ8QDB','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1550,61,'TRB-VSAX82','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1551,61,'TRB-8AHV4U','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1552,61,'TRB-TQ9QH6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1553,61,'TRB-LS7ZRJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1554,61,'TRB-K3W68X','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1555,61,'TRB-C7Z88Q','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1556,61,'TRB-M6KY6J','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1557,61,'TRB-VTLJZJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1558,61,'TRB-PLK5XZ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1559,61,'TRB-S8CAZ3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1560,61,'TRB-YC3ANH','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1561,61,'TRB-39SD29','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1562,61,'TRB-JSRVJG','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1563,61,'TRB-SRZAFF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1564,61,'TRB-U3WEXN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1565,61,'TRB-DWN5YU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1566,61,'TRB-TJ5EN3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1567,61,'TRB-EMAE6Q','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1568,61,'TRB-MAD9RD','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1569,61,'TRB-LZJNAE','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1570,61,'TRB-Z3BY3L','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1571,61,'TRB-PJUB6J','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1572,61,'TRB-KF4NTN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1573,61,'TRB-4AM58M','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1574,61,'TRB-5QKS6T','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1575,61,'TRB-URWNR8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1576,61,'TRB-LSQVKG','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1577,61,'TRB-5QKF6D','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1578,61,'TRB-WPCPQN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:09:33',NULL,'ninguno',0,0,0),(1579,61,'TRB-7SGGSP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1580,61,'TRB-4UWNJV','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1581,61,'TRB-RPNTRA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1582,61,'TRB-C9VGLT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1583,61,'TRB-9YU5L3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1584,61,'TRB-YYNVGN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1585,61,'TRB-FA3UGK','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1586,61,'TRB-N6F88T','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1587,61,'TRB-C7CLKJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1588,61,'TRB-9VD27U','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1589,61,'TRB-ACBRT9','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1590,61,'TRB-VGNMX8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1591,61,'TRB-YCJSGP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1592,61,'TRB-DJVJ2L','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1593,61,'TRB-SSHHG4','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1594,61,'TRB-NSWFQ2','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1595,61,'TRB-JLZEKC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1596,61,'TRB-6JRRAW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:02',NULL,'ninguno',0,0,0),(1597,61,'TRB-UDLSLQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1598,61,'TRB-R5EM52','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1599,61,'TRB-87URCU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1600,61,'TRB-5YZDK9','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1601,61,'TRB-DMRGQF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1602,61,'TRB-LCJUV2','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1603,61,'TRB-LHFF26','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1604,61,'TRB-JBMFFU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1605,61,'TRB-CV8B23','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1606,61,'TRB-42BNY3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1607,61,'TRB-QQPSYA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1608,61,'TRB-BYJY9P','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1609,61,'TRB-JNRPGQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1610,61,'TRB-2CVMMA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1611,61,'TRB-Z6HK3W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1612,61,'TRB-AKQ8HP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1613,61,'TRB-ENCDYZ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1614,61,'TRB-MRNEQY','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1615,61,'TRB-NXCTY6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1616,61,'TRB-BWUQNC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1617,61,'TRB-2C8HHH','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1618,61,'TRB-KGTW7C','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1619,61,'TRB-7CBBCK','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1620,61,'TRB-8RPDVJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1621,61,'TRB-5ZY5RA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1622,61,'TRB-WLURMF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1623,61,'TRB-4LWDJL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1624,61,'TRB-44BZHC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1625,61,'TRB-UEA6SK','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1626,61,'TRB-XPA8YU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:20',NULL,'ninguno',0,0,0),(1627,61,'TRB-UCVK5A','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1628,61,'TRB-S65B4W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1629,61,'TRB-6LS7NU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1630,61,'TRB-5FZH22','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1631,61,'TRB-GL2ER8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1632,61,'TRB-JXBFM7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1633,61,'TRB-HLGNCM','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1634,61,'TRB-5PVCHY','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1635,61,'TRB-6XNUBW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1636,61,'TRB-82FRA2','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1637,61,'TRB-LP8EAG','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1638,61,'TRB-JF7J2K','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1639,61,'TRB-ZKT967','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1640,61,'TRB-E9UEFF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1641,61,'TRB-BKDYPU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1642,61,'TRB-J4THWF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1643,61,'TRB-AHMT3F','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1644,61,'TRB-BKFJA2','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1645,61,'TRB-S9LGDC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1646,61,'TRB-9G6YPV','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1647,61,'TRB-A3MSWL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1648,61,'TRB-9QHT3W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1649,61,'TRB-PK6V6E','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1650,61,'TRB-ELSZRD','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1651,61,'TRB-ZJSPJV','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1652,61,'TRB-XAWLN9','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1653,61,'TRB-AGPFM6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1654,61,'TRB-WFQLVX','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1655,61,'TRB-G5JWG4','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1656,61,'TRB-FGW6CS','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1657,61,'TRB-XNBAVE','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1658,61,'TRB-FD26B6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1659,61,'TRB-AYHZP5','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1660,61,'TRB-HG96VD','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1661,61,'TRB-KQ8ZZX','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1662,61,'TRB-8J7F4K','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1663,61,'TRB-T37Z2C','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1664,61,'TRB-L2Y5D6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1665,61,'TRB-J6WBV3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1666,61,'TRB-8SQRSF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1667,61,'TRB-DFCLTC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1668,61,'TRB-MXBVQZ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1669,61,'TRB-RW7TDQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1670,61,'TRB-L3PSMU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1671,61,'TRB-C633Y7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1672,61,'TRB-XQCW37','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1673,61,'TRB-LK3M6V','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1674,61,'TRB-GU95WW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1675,61,'TRB-5GRD68','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1676,61,'TRB-3EMY4H','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:36:59',NULL,'ninguno',0,0,0),(1677,61,'TRB-CGX3K8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,0,0),(1678,61,'TRB-WYH4DT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,0,0),(1679,61,'TRB-977KJB','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,0,0),(1680,61,'TRB-HA7ZK6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,0,0),(1681,61,'TRB-9WAB4W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1682,61,'TRB-W6LS9E','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1683,61,'TRB-J8FSQ5','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1684,61,'TRB-4SLZ74','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1685,61,'TRB-5UBJMS','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1686,61,'TRB-E8HA95','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1687,61,'TRB-7V33WQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1688,61,'TRB-RUMCJT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1689,61,'TRB-U3A68L','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1690,61,'TRB-D8AQLL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1691,61,'TRB-W94X7U','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1692,61,'TRB-8SUGFE','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1693,61,'TRB-7GXTKL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1694,61,'TRB-7YFRXR','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1695,61,'TRB-VJEKKN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1696,61,'TRB-AG9BHH','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1697,61,'TRB-KRTYCA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1698,61,'TRB-X2SVUM','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1699,61,'TRB-TGPK6T','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1700,61,'TRB-SESYHC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1701,61,'TRB-5Z7CDK','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1702,61,'TRB-QDZUS6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1703,61,'TRB-P8YMCW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1704,61,'TRB-9BLX23','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1705,61,'TRB-XJ2M5T','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1706,61,'TRB-ACSVLE','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1707,61,'TRB-WCGBKU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1708,61,'TRB-XR9WSN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1709,61,'TRB-LXLLLJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1710,61,'TRB-M7XL6C','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1711,61,'TRB-6L6PNT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1712,61,'TRB-T3569C','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1713,61,'TRB-BXDWUF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1714,61,'TRB-TCBM9U','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1715,61,'TRB-MYGKU5','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1716,61,'TRB-ZHV8W8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1717,61,'TRB-V73RMP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1718,61,'TRB-5AWQFC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1719,61,'TRB-NWLJVB','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1720,61,'TRB-89Z8ZL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1721,61,'TRB-V8ZP25','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1722,61,'TRB-XNARH3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1723,61,'TRB-GHXEH5','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1724,61,'TRB-HJMUXG','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1725,61,'TRB-V2RHNA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1726,61,'TRB-MCD7TA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1727,61,'TRB-FBJZHV','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1728,61,'TRB-KZ52FC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1729,61,'TRB-HYQZ7B','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1730,61,'TRB-ZU5VFA','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1731,61,'TRB-J7MS5J','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1732,61,'TRB-U9EA72','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1733,61,'TRB-7J8WKF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1734,61,'TRB-SJBYZ6','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1735,61,'TRB-VT366T','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1736,61,'TRB-W42349','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1737,61,'TRB-35K3K5','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1738,61,'TRB-H8UCE7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1739,61,'TRB-QT5ZUZ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1740,61,'TRB-ZULUAU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1741,61,'TRB-8Z69VQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1742,61,'TRB-6JSR68','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1743,61,'TRB-D66H8J','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1744,61,'TRB-G9BSWX','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1745,61,'TRB-DTCAVT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1746,61,'TRB-BPT6PW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1747,61,'TRB-FSJS4H','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1748,61,'TRB-AD8YNS','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1749,61,'TRB-KJ6NYB','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1750,61,'TRB-X25KZ7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1751,61,'TRB-TD9JRK','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1752,61,'TRB-7MY8LB','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1753,61,'TRB-4WD6M8','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1754,61,'TRB-ESWKJU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1755,61,'TRB-EJU9BN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1756,61,'TRB-6TT7BU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1757,61,'TRB-QABBC4','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1758,61,'TRB-88C7DW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1759,61,'TRB-USPHUT','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1760,61,'TRB-X8YGBP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1761,61,'TRB-GWV5CN','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1762,61,'TRB-WJTJZF','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1763,61,'TRB-7YRZW7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1764,61,'TRB-7T3JYG','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1765,61,'TRB-D9DCLP','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1766,61,'TRB-74T3R7','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1767,61,'TRB-2VNG3W','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1768,61,'TRB-TTF8E9','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1769,61,'TRB-24C5LW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1770,61,'TRB-ER33PU','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1771,61,'TRB-GRH5XW','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1772,61,'TRB-YSQLEJ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1773,61,'TRB-T72YL3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1774,61,'TRB-GBUS9U','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1775,61,'TRB-66TJMX','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1776,61,'TRB-2MSCYC','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:37:36',NULL,'ninguno',0,1,0),(1777,61,'TRB-PZEUE5','observacion','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',1,'observacion',0,0,0),(1778,61,'TRB-YUJ6U7','malogrado','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1779,61,'TRB-EV7FD5','malogrado','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1780,61,'TRB-GGS9XL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1781,61,'TRB-QTQP25','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1782,61,'TRB-FLNJEL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1783,61,'TRB-F2ZQQS','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1784,61,'TRB-KAT8X3','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1785,61,'TRB-756JKQ','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0),(1786,61,'TRB-HJL9CL','disponible','{\"MAC\":\"\",\"SN\":\"\",\"IP\":\"\"}','2026-06-02 04:45:58',NULL,'ninguno',0,0,0);
/*!40000 ALTER TABLE `inventory_skus` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory_stock_log`
--

DROP TABLE IF EXISTS `inventory_stock_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inventory_stock_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `sku_codes` text DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_stock_log`
--

LOCK TABLES `inventory_stock_log` WRITE;
/*!40000 ALTER TABLE `inventory_stock_log` DISABLE KEYS */;
INSERT INTO `inventory_stock_log` VALUES (1,34,10,'[\"TRB-DLYTVB\",\"TRB-DBK8LG\",\"TRB-MPHXKX\",\"TRB-XQY2JM\",\"TRB-7GZQZY\",\"TRB-3E2VN4\",\"TRB-GBBLJG\",\"TRB-G72LPE\",\"TRB-J48P6B\",\"TRB-J59232\",\"TRB-XXN5V8\",\"TRB-R4FEC9\",\"TRB-H2H8E7\",\"TRB-PTXPW4\",\"TRB-4QSZ6H\",\"TRB-7EE45U\",\"TRB-X9R34E\",\"TRB-SSPXPB\",\"TRB-WC7XCF\",\"TRB-WF55E9\"]',1,'','2026-05-25 17:30:13'),(2,30,10,'[]',1,'','2026-05-25 17:30:52'),(3,37,5,'[]',1,'','2026-05-25 17:31:04'),(4,34,15,'[\"TRB-F8CQ82\",\"TRB-SMK53Q\",\"TRB-XYP45Q\",\"TRB-EEVQ8N\",\"TRB-Z6SX4S\",\"TRB-KZ7DPL\",\"TRB-JD5EA2\",\"TRB-K4JX8S\",\"TRB-SAJJPL\",\"TRB-VUE9AH\",\"TRB-YSVKWF\",\"TRB-S4D4ZC\",\"TRB-CHZNW2\",\"TRB-Z6HZQE\",\"TRB-C6QC74\"]',1,'','2026-05-25 17:43:38'),(5,47,10,'[\"TRB-ZS5HKP\",\"TRB-CGFLK3\",\"TRB-TC3LYC\",\"TRB-QC84PQ\",\"TRB-Y3NEB9\",\"TRB-3QPAEU\",\"TRB-LASLM7\",\"TRB-JG5FSQ\",\"TRB-5DJB3X\",\"TRB-9LF2UN\"]',1,'','2026-05-25 23:05:09'),(6,47,10,'[\"TRB-N6WX9S\",\"TRB-67LM6N\",\"TRB-8MCG8D\",\"TRB-PCPQS3\",\"TRB-WRRSFS\",\"TRB-LLGM6D\",\"TRB-UR49DN\",\"TRB-5AENZ6\",\"TRB-52NJM9\",\"TRB-HW6GWU\"]',1,'','2026-05-25 23:05:11'),(7,47,10,'[\"TRB-NBKV3F\",\"TRB-UYE6E8\",\"TRB-P37XLR\",\"TRB-3XCP29\",\"TRB-SDCDY3\",\"TRB-97QPXG\",\"TRB-W7DWUG\",\"TRB-R4VYWF\",\"TRB-YDBVPJ\",\"TRB-E43GTP\"]',1,'','2026-05-25 23:05:12'),(8,47,10,'[\"TRB-77QG4G\",\"TRB-YCCAVH\",\"TRB-ZQ9R5T\",\"TRB-XCRFAV\",\"TRB-7JUVU3\",\"TRB-V5KHM8\",\"TRB-L3P7B8\",\"TRB-79CSC9\",\"TRB-Q8AHA7\",\"TRB-YSFDTR\"]',1,'','2026-05-25 23:05:12'),(9,47,10,'[\"TRB-2KPQYJ\",\"TRB-78N7FF\",\"TRB-HRM8K8\",\"TRB-PN2362\",\"TRB-PAZNJT\",\"TRB-XJ82AS\",\"TRB-QA4UAC\",\"TRB-HYFUWF\",\"TRB-Y5N9G4\",\"TRB-V6G4CB\"]',1,'','2026-05-25 23:05:12'),(10,47,10,'[\"TRB-E7QMJB\",\"TRB-Z6VY6D\",\"TRB-TX8RT3\",\"TRB-GWC2JS\",\"TRB-MGMYXS\",\"TRB-4ACAQY\",\"TRB-L6WSWH\",\"TRB-PX3KA9\",\"TRB-BQEWT5\",\"TRB-XQ2WF6\"]',1,'','2026-05-25 23:05:12'),(11,47,10,'[\"TRB-VL224Y\",\"TRB-47SS56\",\"TRB-XT4L3D\",\"TRB-5XP4KL\",\"TRB-CD4THE\",\"TRB-RYBXTY\",\"TRB-ZBKP6U\",\"TRB-ZGG8CR\",\"TRB-8C9ZEK\",\"TRB-SWJEKH\"]',1,'','2026-05-25 23:05:12'),(12,47,10,'[\"TRB-VVPTHS\",\"TRB-XS3VX8\",\"TRB-QY7B63\",\"TRB-78SZ94\",\"TRB-HZBAAB\",\"TRB-DKS2GK\",\"TRB-BDGTAF\",\"TRB-9EZT29\",\"TRB-NEWZZJ\",\"TRB-V8T2LX\"]',1,'','2026-05-25 23:05:13'),(13,47,10,'[\"TRB-DFM83X\",\"TRB-XC96QY\",\"TRB-T3XCN3\",\"TRB-8TSKZZ\",\"TRB-8F9GFG\",\"TRB-2QG9VP\",\"TRB-B9EWE4\",\"TRB-RVLULU\",\"TRB-6STYUV\",\"TRB-NYZ7D3\"]',1,'','2026-05-25 23:05:13'),(14,47,10,'[\"TRB-EJVZTK\",\"TRB-NF3LRA\",\"TRB-FKUVLF\",\"TRB-6TNQ9Q\",\"TRB-TAJ5K4\",\"TRB-475BRQ\",\"TRB-ZX43DL\",\"TRB-XR4FWV\",\"TRB-RNBY9E\",\"TRB-XWMH9Y\"]',1,'','2026-05-25 23:05:13'),(15,47,10,'[\"TRB-ZCMF9C\",\"TRB-73USW8\",\"TRB-Z33EGS\",\"TRB-LL84MC\",\"TRB-HAWZWY\",\"TRB-YH3RBD\",\"TRB-CPGKMN\",\"TRB-GM92CA\",\"TRB-BKJFCX\",\"TRB-LUHDHP\"]',1,'','2026-05-25 23:05:13'),(16,47,10,'[\"TRB-6YA869\",\"TRB-23N8BU\",\"TRB-M8G8XF\",\"TRB-6998BL\",\"TRB-6LVW58\",\"TRB-JPX7Z5\",\"TRB-84ZP9B\",\"TRB-WYTGBG\",\"TRB-3KPMYQ\",\"TRB-DPTB6E\"]',1,'','2026-05-25 23:05:13'),(17,47,10,'[\"TRB-TW8MFX\",\"TRB-S6YHN9\",\"TRB-MZBDQJ\",\"TRB-743ABQ\",\"TRB-H389QS\",\"TRB-6ZQD6B\",\"TRB-K54DCK\",\"TRB-E4G8Z5\",\"TRB-PCNHCF\",\"TRB-AXH2CY\"]',1,'','2026-05-25 23:07:56'),(18,47,25,'[\"TRB-BJCKBP\",\"TRB-23D3RW\",\"TRB-DSLDJT\",\"TRB-42S385\",\"TRB-C2KTNQ\",\"TRB-T7ZUND\",\"TRB-8C5DEC\",\"TRB-FC9WF8\",\"TRB-PBW8ZZ\",\"TRB-XK5XGF\",\"TRB-3B95KV\",\"TRB-D45UXU\",\"TRB-ZPLVW2\",\"TRB-2HNWAG\",\"TRB-78ARFL\",\"TRB-LSFPF5\",\"TRB-MU7V2P\",\"TRB-T5ZBEG\",\"TRB-6W9CQE\",\"TRB-L3G32S\",\"TRB-9GRJHF\",\"TRB-HEQ6S5\",\"TRB-TX2QV3\",\"TRB-CT82UP\",\"TRB-HZ4JL5\"]',1,'','2026-05-25 23:10:26'),(19,48,25,'[]',1,'Ingreso de lote agrupado','2026-05-25 23:17:44'),(20,48,20,'[]',1,'Ingreso de lote agrupado','2026-05-25 23:17:59'),(21,48,3,'[]',1,'Ingreso de lote agrupado','2026-05-26 12:03:07'),(22,56,-10,'[]',1,'Ajuste variante: 50 → 40','2026-05-27 12:18:48'),(23,58,-15,'[]',1,'Ajuste variante: 60 → 45','2026-05-27 12:18:48'),(24,37,-9,'[]',1,'Ajuste directo de stock: 19 → 10','2026-05-27 12:18:54'),(25,33,-5,'[\"TRB-JCBXKZ\",\"TRB-X6ZHFS\",\"TRB-ZA6HJS\",\"TRB-DHV79J\",\"TRB-9AQMES\"]',1,'Ajuste directo de stock: 10 → 5','2026-05-27 12:19:03'),(26,59,10,'[\"TRB-N3CRYR\",\"TRB-JV5N98\",\"TRB-4HKBHM\",\"TRB-BED5M3\",\"TRB-Q3TV6D\",\"TRB-JUKFGZ\",\"TRB-EJ3V4L\",\"TRB-9UBLYH\",\"TRB-5SUJG2\",\"TRB-83S36W\"]',1,'Ajuste directo de stock: 10 → 20','2026-05-29 12:36:55'),(27,60,20,'[\"TRB-DLPUS4\",\"TRB-U28XP2\",\"TRB-XTXA9M\",\"TRB-KXGEQ2\",\"TRB-XH49P6\",\"TRB-2WGN4N\",\"TRB-ETLZ2X\",\"TRB-L9KLPD\",\"TRB-6FCQJ8\",\"TRB-GN9ENB\",\"TRB-KAZQ9L\",\"TRB-BKC7U2\",\"TRB-GQTPMP\",\"TRB-BUADSU\",\"TRB-8ZETME\",\"TRB-73W9ET\",\"TRB-UEX2Y5\",\"TRB-SSWR4F\",\"TRB-LDPBSJ\",\"TRB-QQVZGD\"]',1,'Ajuste directo de stock: 10 → 30','2026-05-29 12:44:08'),(28,61,18,'[\"TRB-7SGGSP\",\"TRB-4UWNJV\",\"TRB-RPNTRA\",\"TRB-C9VGLT\",\"TRB-9YU5L3\",\"TRB-YYNVGN\",\"TRB-FA3UGK\",\"TRB-N6F88T\",\"TRB-C7CLKJ\",\"TRB-9VD27U\",\"TRB-ACBRT9\",\"TRB-VGNMX8\",\"TRB-YCJSGP\",\"TRB-DJVJ2L\",\"TRB-SSHHG4\",\"TRB-NSWFQ2\",\"TRB-JLZEKC\",\"TRB-6JRRAW\"]',1,'Ajuste directo de stock: 50 → 68','2026-06-01 23:36:02'),(29,61,30,'[\"TRB-UDLSLQ\",\"TRB-R5EM52\",\"TRB-87URCU\",\"TRB-5YZDK9\",\"TRB-DMRGQF\",\"TRB-LCJUV2\",\"TRB-LHFF26\",\"TRB-JBMFFU\",\"TRB-CV8B23\",\"TRB-42BNY3\",\"TRB-QQPSYA\",\"TRB-BYJY9P\",\"TRB-JNRPGQ\",\"TRB-2CVMMA\",\"TRB-Z6HK3W\",\"TRB-AKQ8HP\",\"TRB-ENCDYZ\",\"TRB-MRNEQY\",\"TRB-NXCTY6\",\"TRB-BWUQNC\",\"TRB-2C8HHH\",\"TRB-KGTW7C\",\"TRB-7CBBCK\",\"TRB-8RPDVJ\",\"TRB-5ZY5RA\",\"TRB-WLURMF\",\"TRB-4LWDJL\",\"TRB-44BZHC\",\"TRB-UEA6SK\",\"TRB-XPA8YU\"]',1,'Ajuste directo de stock: 50 → 80','2026-06-01 23:36:20'),(30,61,50,'[\"TRB-UCVK5A\",\"TRB-S65B4W\",\"TRB-6LS7NU\",\"TRB-5FZH22\",\"TRB-GL2ER8\",\"TRB-JXBFM7\",\"TRB-HLGNCM\",\"TRB-5PVCHY\",\"TRB-6XNUBW\",\"TRB-82FRA2\",\"TRB-LP8EAG\",\"TRB-JF7J2K\",\"TRB-ZKT967\",\"TRB-E9UEFF\",\"TRB-BKDYPU\",\"TRB-J4THWF\",\"TRB-AHMT3F\",\"TRB-BKFJA2\",\"TRB-S9LGDC\",\"TRB-9G6YPV\",\"TRB-A3MSWL\",\"TRB-9QHT3W\",\"TRB-PK6V6E\",\"TRB-ELSZRD\",\"TRB-ZJSPJV\",\"TRB-XAWLN9\",\"TRB-AGPFM6\",\"TRB-WFQLVX\",\"TRB-G5JWG4\",\"TRB-FGW6CS\",\"TRB-XNBAVE\",\"TRB-FD26B6\",\"TRB-AYHZP5\",\"TRB-HG96VD\",\"TRB-KQ8ZZX\",\"TRB-8J7F4K\",\"TRB-T37Z2C\",\"TRB-L2Y5D6\",\"TRB-J6WBV3\",\"TRB-8SQRSF\",\"TRB-DFCLTC\",\"TRB-MXBVQZ\",\"TRB-RW7TDQ\",\"TRB-L3PSMU\",\"TRB-C633Y7\",\"TRB-XQCW37\",\"TRB-LK3M6V\",\"TRB-GU95WW\",\"TRB-5GRD68\",\"TRB-3EMY4H\"]',1,'Ajuste directo de stock: 50 → 100','2026-06-01 23:36:59'),(31,61,100,'[\"TRB-CGX3K8\",\"TRB-WYH4DT\",\"TRB-977KJB\",\"TRB-HA7ZK6\",\"TRB-9WAB4W\",\"TRB-W6LS9E\",\"TRB-J8FSQ5\",\"TRB-4SLZ74\",\"TRB-5UBJMS\",\"TRB-E8HA95\",\"TRB-7V33WQ\",\"TRB-RUMCJT\",\"TRB-U3A68L\",\"TRB-D8AQLL\",\"TRB-W94X7U\",\"TRB-8SUGFE\",\"TRB-7GXTKL\",\"TRB-7YFRXR\",\"TRB-VJEKKN\",\"TRB-AG9BHH\",\"TRB-KRTYCA\",\"TRB-X2SVUM\",\"TRB-TGPK6T\",\"TRB-SESYHC\",\"TRB-5Z7CDK\",\"TRB-QDZUS6\",\"TRB-P8YMCW\",\"TRB-9BLX23\",\"TRB-XJ2M5T\",\"TRB-ACSVLE\",\"TRB-WCGBKU\",\"TRB-XR9WSN\",\"TRB-LXLLLJ\",\"TRB-M7XL6C\",\"TRB-6L6PNT\",\"TRB-T3569C\",\"TRB-BXDWUF\",\"TRB-TCBM9U\",\"TRB-MYGKU5\",\"TRB-ZHV8W8\",\"TRB-V73RMP\",\"TRB-5AWQFC\",\"TRB-NWLJVB\",\"TRB-89Z8ZL\",\"TRB-V8ZP25\",\"TRB-XNARH3\",\"TRB-GHXEH5\",\"TRB-HJMUXG\",\"TRB-V2RHNA\",\"TRB-MCD7TA\",\"TRB-FBJZHV\",\"TRB-KZ52FC\",\"TRB-HYQZ7B\",\"TRB-ZU5VFA\",\"TRB-J7MS5J\",\"TRB-U9EA72\",\"TRB-7J8WKF\",\"TRB-SJBYZ6\",\"TRB-VT366T\",\"TRB-W42349\",\"TRB-35K3K5\",\"TRB-H8UCE7\",\"TRB-QT5ZUZ\",\"TRB-ZULUAU\",\"TRB-8Z69VQ\",\"TRB-6JSR68\",\"TRB-D66H8J\",\"TRB-G9BSWX\",\"TRB-DTCAVT\",\"TRB-BPT6PW\",\"TRB-FSJS4H\",\"TRB-AD8YNS\",\"TRB-KJ6NYB\",\"TRB-X25KZ7\",\"TRB-TD9JRK\",\"TRB-7MY8LB\",\"TRB-4WD6M8\",\"TRB-ESWKJU\",\"TRB-EJU9BN\",\"TRB-6TT7BU\",\"TRB-QABBC4\",\"TRB-88C7DW\",\"TRB-USPHUT\",\"TRB-X8YGBP\",\"TRB-GWV5CN\",\"TRB-WJTJZF\",\"TRB-7YRZW7\",\"TRB-7T3JYG\",\"TRB-D9DCLP\",\"TRB-74T3R7\",\"TRB-2VNG3W\",\"TRB-TTF8E9\",\"TRB-24C5LW\",\"TRB-ER33PU\",\"TRB-GRH5XW\",\"TRB-YSQLEJ\",\"TRB-T72YL3\",\"TRB-GBUS9U\",\"TRB-66TJMX\",\"TRB-2MSCYC\"]',1,'Ajuste directo de stock: 50 → 150','2026-06-01 23:37:36'),(32,61,10,'[\"TRB-PZEUE5\",\"TRB-YUJ6U7\",\"TRB-EV7FD5\",\"TRB-GGS9XL\",\"TRB-QTQP25\",\"TRB-FLNJEL\",\"TRB-F2ZQQS\",\"TRB-KAT8X3\",\"TRB-756JKQ\",\"TRB-HJL9CL\"]',1,'Ajuste directo de stock: 150 → 160','2026-06-01 23:45:58');
/*!40000 ALTER TABLE `inventory_stock_log` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory_user_stock`
--

LOCK TABLES `inventory_user_stock` WRITE;
/*!40000 ALTER TABLE `inventory_user_stock` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory_user_stock` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `login_attempts`
--

DROP TABLE IF EXISTS `login_attempts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `identifier` varchar(255) NOT NULL,
  `attempt_time` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `login_attempts`
--

LOCK TABLES `login_attempts` WRITE;
/*!40000 ALTER TABLE `login_attempts` DISABLE KEYS */;
/*!40000 ALTER TABLE `login_attempts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas_elementos`
--

DROP TABLE IF EXISTS `mapas_elementos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_elementos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `proyecto_id` int(11) NOT NULL,
  `tipo` varchar(50) NOT NULL,
  `nombre` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `geojson` text NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `icono` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `capacidad_puertos` int(11) DEFAULT 0,
  `potencia_dbm` varchar(50) DEFAULT '',
  `cable_origen` varchar(100) DEFAULT '',
  `splitter_tipo` varchar(50) DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `proyecto_id` (`proyecto_id`),
  CONSTRAINT `mapas_elementos_ibfk_1` FOREIGN KEY (`proyecto_id`) REFERENCES `mapas_proyectos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=99 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas_elementos`
--

LOCK TABLES `mapas_elementos` WRITE;
/*!40000 ALTER TABLE `mapas_elementos` DISABLE KEYS */;
INSERT INTO `mapas_elementos` VALUES (1,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwvJJx_3hfSABXMiupIiy1hJ3Nfy8dJJ6uCEj2DeMUtmOi7E74HXjwaqgDtw7khltL_3koSCU4wJSvou7VojjPSok580eF5cJ-DNK8m7MOuSqLUXSi8DKwuxfq9XSl029c-Yr2Wb1T8BU04cDq_EisqC_NBBvicBuxtWh5P1AyY36_lDVAxosaWXjKQ0ne1i6LxhDlB8Y65b7VPRoBGwucMe2QmcWwELhErJ2Xd07vfVRiyplcri2ChMLk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwYFZyRvrtBB1AuBGwIxYRBflG1Z_MCnChabxF9G4Sz4g7crnxkLTkGwiCS2ZpRZ5pL75qDpVbNqDpbuYJqi6yJTfXQ5Hb7uHQ2lNWFn7EPSzXUw2ZSaNLppkVyjNZnWm276IcN6IslOmJWKKHMvIbg62cF-14cYY337PhJiZHUDUynFBPXUqmddFGGPTDSdmPqufohcG0lSq1ErEy2q87ihlWgdVJCJ6qKVbSs6l4o68AgbqhwTULAgbU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxte7v6X-AV5l46xB0KzMUz57-KHG_97igZE7NTGvTReQrsKPjrwPC4G3_Pd5c57B0xGirIsJpbP4nE85YnVpuy3JbbgWmJz_g78NxBNEZjJDcSG22SNkc4wUnmAFSKUOyuqPYh_KCh9iHJX9Ao-wqoFFJBtv0V7GRJaMrFtMEXEqRyt_aID8mGAAzrTJ0C4kVP735MGakKqdRpzs659NVI5pB7XGnp6d3bLOBMzU9DHdfwCiO1iJnnZQA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1078046,-11.8475511,0]}','#facc15','ph-map-pin','2026-06-03 01:43:15',0,'','',''),(2,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxuj_Rniuu-mxUPdGSqbHWKLCihP_1hI5HXqWJ0uCPEwzLM7dbJnYbb2PmuaqvAzFGxD7SRiYOvjhRwOl_dbHkhhTZjTFsVyZSkvYAwS3Of4Mnyl4t2X6VDCv9-Gfu_DQXR6Y3mDeWbGIysxUU7-Xpoy5YQVjOmhrFp7zgCh6U-jotQQ-r6zTk6caU_J7C0zN4997wCS8nd9y3ImLWnADb5gN7Cg_NigFsBUH_mjhMt0MN02T8AVu8q0r4?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO GRIS - ASU 12 HILOS - TERMINA EN PUNTA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyCvyYKg-GK4vUa5jxUd3bmo6kK_nyYQxhhmNYd0oH9Cq1Qmxw8Of09wB4Nd1g-kDg2Zs1m7DRus0IG3Qd51tqTnuQArTJ57M_XEr5bjNPHBEz8XR2OYFYy5ZRc7RfQZw-VFIrsA2bIly4LAPd6U3tVfB-v8yz2-LYBScPgr-YuVlpndxREMawk-NMiq9P1Y1rxJoI2wCDovcoNyUraAOdqjIyMiDNHdvbu5zwuICcTChff2uRHVnBTQWk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1100538,-11.8490632,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(3,1,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwf8lHqOaRYsb7JjnyE7P0fTMlIf9hkX8B-JnR5s34ZHKGwh2z7VGif_zJ2JzsBCrNWRGdQPEes-vXyve25HOfL6MkoJ2HjSbDTYaQS1UtU4mliTWjLtL7BCKMqPxnujVUwzvOFxbm_bBXm2yMgiBfN-unxAl_A5Bx0hsYuowtuA48Zi24-Q93zL5qNvwpkVmlPQWieuITKzQRgk8ZSDIbzV-EqTs0-lKHWdNc8ooSpsG_UD-G6MkQAgS0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - ASU 12 HILOS - SANGRADO - ENTRA UNA PUNTA Y ESTA FUSIONADO CON HILO MARRON Y AZUL','{\"type\":\"Point\",\"coordinates\":[-77.1097904,-11.849518,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(4,1,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5aTDJ7wuN1dnKiCKiJ3bHEKQQgb9SoGiDgDlsUpjzoYlg06jNN9YyLsO5UGvPJrW3iK1nWppXVTdd_ezRyXhEAuWrWl8rEqzshl4Rn65vYDmboCFA8FpMCxAwsj5Zl4DLU1V9IJWWYlOn5n6cbDUf2HM9XEoUMiKSHQlrvYCPZM5Uxx1TEXPZHwv36aHTwEBiMAcDRURsSWIir0wTH2NycyKtXyJFIhxGvcvO-I2zZq722HCqHJGa8NU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS -TERMINA EN PUNTA','{\"type\":\"Point\",\"coordinates\":[-77.1104302,-11.849734,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(5,1,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GweJJWX8WjtIjiwDqe4IWc2dSyjXC0tkN4U0mDiNfJK4sKpMg0DcP1FF-Xga-WpXSTxK5LHe2iIrpS8D0Q9rBLgezTWTIOk9_Xk73GkUpIMJ8QMmLXk-_qLW8kXmhgnxjviJ2PMN9JTzP9VSb1351Ulr6rXGKI5IfVY3GilB09hFYiI9h5A3XGj6nD-Q0e1TBzYJg6UW0emECeji2aVxG4A908dWSd0S7iosVHQ0lqWzWhJZ3qLWz8fYwA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA - POTENCIA -22 / ASU 12 HILOS - SANGRADO - ENTRA CABLE ASU DE 12 HILOS PUNTA LA FUCION HILO AZUL HILO BLANCO<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwcQkxSiMXsRNyKtqLtY67Doi-vRchhScI7Eub2yzigztUrFrPp02LFSlsyd_0CZ43TrEd_XDNEwDfDvSUsMj9Iwlncu8LXny1U2yvWvLm10bTbVMM_fhqiN9mg7cPWZSJydRcyOSEZiTtenNisHJCFC-qIG4pULMuF9AjceAfMMy4kxqNiBPnOU5fx-Axa2DdFymFv5lXJBaQLwRBco_jl-a12jbmdRqOMk8iZaN8o1Gzs4srRGWqix1c?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwUpJkx9w3OuBCTEKPg-VRhdbwRlyZepba8fonZTdgzcc1HQYZud2QPdq4vw3VHPTzoF3KJMSwSefA2-QxTWSkz1R_fJ8aYKyv_TkxkK-P-rfghTXiW9Lz1jrJpZw9bOzfHKpjGAecj3bj-SFaJHa55Mq5KkWoylhM1hUArnxhGS_Au27VZFi39CNU6kkFy7TJk7BoHIwHhBKyfS2ybpAeQmuGaATOhXtcmqtrfzXvM58T59mhbK0vaPbc?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1092775,-11.8490866,0]}','#facc15','ph-map-pin','2026-06-03 01:43:15',0,'','',''),(6,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzAw7bwcJjXrlnS58bzxMT58xOpoltkQ8qZOTy_jcz7Vf1zIq9vmjrtfYPFS_x7pbB8I20MGbabfEKJR_GolLcC0z0NBwyxn6p53-XPF5t8jRQrxcR8afbB6NBIp_tZOVHgA9NUJs-zKqZxhmVmiqvvKtCoHTEge87KEzXLcU9BDE2Yt5WhTCzbhBufiHeRVvPOYCYLC5aaLEv6Xm3vbWIpVHH10VjY9zvqOg4wc9NXQGLOK6QxekV7K0I?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - POTENCIA -23  /  TERMINA EN PUNTA -CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwLrnDuL54DzvUKj26vOlxrubBj2gL1E9y8vacuhX_FjF51CSnZehzVg8mdsZ2f-BLmScAtCuesCN9W4UIJ_oUs_0ODbWCVsGkl6b5WwRLuhTAJAbmshz5SlpYeFeKUK9ZUCEurUjA3Dh6PvHudhnT7yN--a9HdP97kgA3XUhxivDpmmyJSEZSbDwAKyFVcSa1iiECQNKWSqUBmvcht45zUJffAE7a1Pw1ju1KNF27s0THuRhoHjT66xjo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz6Ida-rcnqvl8hpDalIdu4-4y1aHpE2jPjob8RdxbagumdmstlBW1kWLGGlq1VsyEoUDPPV2OXI9-BC5IGNGBWlNUT-DBUcbWd_4lWw2NRhNtoCunomF78aNafEO0sNgu3txgWUJXjIki2IiIjIBz7qx1GkerlehQoQuJfSj5VE7pjamGzuUNCfNRF1P-qxQhfI5lU1JvQorpxzJB7O5PoVJPdjdE5xPZiAapk_K8gYE3SlPiMIHr63WE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzZy4dH8Dx_j6ezYrz52xKgDZoH3xAhiW89Al2rM1mooF_2y-w_c7HEOHeFVSvL3rt-HfxMONUZq45W1gHK9EptNRR49musWKHkYpBXVZNbf-DdB2k6JgE1u_UxdXUCTWgKQamMrbTlHGqK2p8dzP7qOS5PBdjQ6PbkTQnRAEZhB0bkx-i1nukgg3YpcTE5XCPwd0kfVhkX0RyiFzTXvda3kDICddKbsMDwCsXTaTX4s-9poldIvyk3nfk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1091457,-11.8486418,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(7,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzkFtlVbB6VD8okBkta91Y1aJ3vF9-Ldbn3Gvm4Q1IJStYC7OpJV3QokPMwykjOvrE2kP4UcNJyXdXoonwkZMhk7H5gsHshkacaopK9x_fVMXJOtoey9YFxXGWyLPiU9WNHiYw83rvrHPzYbKCSEftYOqdIfLJdugsHnke9uQyN8CtckZUsgNrzP850mBmcsJFAVYw0WmodQlmpk-xRXVOuHs64PG2m7FoAH6VZqcAnPpIa4GV3FvQU7IM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS - POTENCIA -24<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyC1hbrPTCv5f6YFbzTEGyOtm_hDc3MzEc6NkH69oYEWmqZMjAgSEd27ogCL_7OFdm0HdTU3Nt9nAbOIcyBIWjTGTVZUG2WV90tRoY6m1buhJoYSXjMgErqWXdQEB3uPkoledv5_8XhU83TsaZJ8zRjTlEYTFnKZFsVrkqcAV1trJj8Yc5Yv9223FFdhj0o-BHaSx3bdanGPrd2TWV5K4K6ouqyL5fnj9-5xN8GMpEaZ-15uznigmIm-wA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxmRr1xRCqetaacJrwrB9TBO1JjKfXEk3MD8HQmPTaw4-zfeVtztU6UJbBFXqOnE4H1EEjABaBzv9bVxlIm5XIOo-kMD2fWhvnGBcY6eCB6-tR6x26xIoGXLso_V58ztdLsaI5LuQPa4RAK77tgn3Heamvpqv8PhravRqGQ97JiN2hGnmjblwRAPmDvSldcSTjqkPfwRlDJ4ACm6_oSyemrGNgNN_68YljGdbPWA2Gh_oq9r_EhxsWoJkM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GySsJmVO32CiOhOL_TId7k1hobPOsm035nRzsMzF_CEaNreRZpQtRi8nKR00ny79t5MI2bZvaMRrU5mqEFM1wh_pu-fQsyAya-YyTk4fOyPIB4b6pNq7wtoP01bPCs1FR1KxHiftzjkUk6M8ta-zVNrJlAhCtMyEQ1Sy0PvyVga2oNotZRmr_1TV1dnB635eTmuu2shQS9bvO-LV0ervW81KGwZjT2ppgqjsJpi8V3tHuRy9nnbwhIEoAo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1087664,-11.8492807,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(8,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzcCIzEJs9zPSfnpLCCsxe_AhH8jLcQ7RzLjUo3C_s9qnqvAtNSc973Hd6K2EVyqJ_PTmSYvy3fpCLFTX-XqyOqF0_NRIbUYeZjHn1gAPMXT7BES6_gDxlAJO-hxpxr3dcD_y5oRim1iW2JNAdYxAX9RzrnmXsgAKGnx3sQcbWUpCsx801fHXYHIPQEyrr85QWPuXYGhYTt7wjkI_Gf_hWn7uSsnvgueUF0y4Y4nbKJa4-vcLqm3CSR1DY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwnqACqik_sjYpbTDgNuv1EmNGFXzpwR-ct6Udz6UwQUVjc_Fu0inYiuq0LR774NN44QR90rjrVS9czyorP-u-LTaJOQpCpOHfHUnPGbmkGmiZwxf5jrSSELgLTqSZMSPgVOZ4G1IGmGyxNN4WTQeD2NQ9sREEcPcUhWvSbH1_LSXauzrUWxNUtCBKpdYEn7rjJWNs1nyANN_A5om3TD_QOb3g12kLHxNWan9utRxzmOQrDwKg9Fw0kYkU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywDDG8oprKaantfesm-XqoVOjQRcVd-_gWwZqJpnMQO74uOSUvjnajtZs1cu9y4KKWYVREWlAE7SRPwkcEghJsrtxmfwpzNb3RncWPrt_g-lOjj3JtG7e5y12-03sYl_0lkjr8RN4xt3DOk8gmC_-4lpPtO7AzCx1L1cK4xmjEnRkkxw5vbkhcx8LBvI6hUN_2WYLvBROJ5xz7yypHVEdp7Q3hXzwDMpjylO7yh43IbJXAAJahNzkta90?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1084441,-11.8490856,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(9,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAl_37pvf4_hp9jSJ_PYeq_ge8KWQz3DNC_KyyvJVvKq8z9n2nxauhm6sdyg48jTcdqf0yDAd2It5BKsGLdgv43mecQ9E89PlCOyp-0Zieof_UCBmppjuOihBc_D8fe1-Q7d8M9OSVTlq2rtrd3YDZU4rheaqNmP-CkKrzsA1PbD9L2NivvI1jvcuYAAZ0DLylwZGcO5v5PTExJyvjDK1G1l9quEeBdpolmKC5dqv0_5Y6QesSWxhU6vY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA <br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywsJ0GMED71uL-UypU2Ko7E9iMXvyw9rLy3KV3ocd9w2WfZ_VMSKluDefyEv_ng5JoAyXSGII4lwHouaOIn6OU7yqav8oHX2tgydY1khX1wiiKCvBlfdIhwERhnEGBYyU8TjjdmaTx6eovmEZDbo-cSCFgQJOXCNfEUkTUm5cmNm6DW1J4PYgwB3x6m2UF4dNjdc5xiuPIfyythTqUcL72liBtQaKKX6H9uV0ZQ4SQT-uKIuxdupVCny0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1080508,-11.8489455,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(10,1,'Point','MUFA PEQUEÑA','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwqwx-GtnzHdDOPa6HgtgBo1rfEaigxj-sFnz4xxWfgO2KUa6DHuJ6irMkndYk_pX2nIXgTU-o8i2GvMpqEjKkFzBPrxkZhT5NWTinjWAmTb9_VS93aJedoduQd8zhJgBx5ts-gCDYyF_uISsltcPv_tUMZKDYEYqojXQtu-NIwA_6rhl9XLeZQBnaIcC0c_fuN6-Q4YbCtVPRG8DAerPuCeBga1ym5BS1HItu0pFe_d8H-7rh0GQP6CVE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz_mHzIFYaoSsrW9DfhKJc44NJFuA1xrpE1nr3sE56TKAnK22vXbR3r2heNNez8Lu2ZePNss17rZJyE7wCiEf4eUWBJKHeU5SMQVmpF-r9XRir_IXMKITa4ctnpwkvHvLa_yxeOsCsVq_vavrIhGRiP8ctFVVK70jW9b5FjTlrrShsZDMMokeYP3K_TIF-DZucLDMSGJYu1H4g7WkxYdVTaXxfhBgghBeUOVbqXcVCUXcve0BjvfrDCKl8?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyttNLZrVzgET0_Kzrq0_6koEr0vLgTxiY3uPGRJfV2pJpryRNMC-62rfng_j87s2unjcIE8q24uakF-v_AGMS9k0-IlPo1jkEtViTpXjvxdqTbVd_oqZ9YliXtmC2hEkGEczhSIQ8y10XWW6o8s5fvA2Co4vOo7XQgu_G6Z18gYdczLP0Re8QGeA-xR56zU_ZCtN_obdnemyL1oeUd-EkPluywbDS6AaGHMvyR_IYXN1-xs-2Hm_YtvAw?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1066682,-11.847964,0]}','#fb923c',NULL,'2026-06-03 01:43:15',0,'','',''),(11,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxMmjp5Wf8yHrPAnQ1NzpGq6J9pFUPmz6DuBYJxyVVXdX5wPZ0LqUMZ8Alpzq1K4Zy9ZXMRdAIV5r19DHWjmQC4MGpGAUmCtdzoxyKO0oLmLgSm-Ro9KdwCxKpp86EkkoVheEUyRCqPxCfarqga10_tSpscMM9MHwN4vdsAyEQP-QxFbRJAakqrsdpqrN9Lkmtp8lUrDwsZ8a5inZkhGZ30QDhbw-9I3L1S_iB69nvA6kf_zK3w6WxRX1Y?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS - EN ESTA CAJA ENCONTRAMOS UN SANGRADO Y 2 PUNTAS ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5r9VlmLri6xPKvzCuCRc1X_Djp0VFmOojvRpgJdbIO16-QRZcGKDIINkIKU3TseUgjNKvbLr7GRqbDc5N4DSRB_-RUd7njsnMpOYOoB-HBj5MCFRDEeu9ZgohiG0WMgnJDJWANOE6ZYSlTOjzAogyEgUE7S14dCcJIif4IwSNipz-UDA7qQfQdtp0maceXnr_vmH6f5GDZVMxvBrkLs2T6jxCkHSnohxD1TuuZERH99UJpGjePMY0470?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gw3eZB35BFpLfZ7fkk-chagzEwMmaHG73dr-uFdvDeBw2DwCCBeJRHdo0BriN-6KTtmAq-dkVt60RIht6-ixhApIKqHAdFdSwy6ZXHgORnqagp2XfE6ply0CT65TgA704bzDPYUHVfei3lRFnHAB2ziNxM3Yn_rR_LDPNiSMYCysM1ln2Z5uOUul6wnro66Wf7AtqVhF11jm_sAA4kFBJ0-UnPNahsZbGTWzoq2KCctAW3I0dKP7iQLIfQ?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAa4c6R0OBVXg4Vp6Skdvq5WyGwrFnHtQIk4MZ_6UHUmtFLqmgpzKZ0WZQvkvnnB3yi2aYZmdvjVjzTZSGd-VHtRTuFeEY7wmdFORwZWz1L7csgqranwSgxmRxAWcLpfZ0sBMxWYJgu4XDq4ZqQmIBfKyokyH39qwfkZN_VCHgK1DWgl62rEWcSyRoWskCOAzyZpCv4eVyVC1rUELB885rmnzWC7BvxSUbMkRWMk7Xc-UN1pTI5AYjplk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwVS6O4aF8Q35BmCAJIwh7EAKhf_NNIs_BGgWOnn0ONzAE3ijHUVXaTOn5WkXPTvmF4mF9DcM_tLCnngSc-a-jmoKYJEkPf2Wv6wB07fOcLq9-ojz39XyIF09tx7ieRuFUPoDelm21YdsbSCjL3VY3ix9dLC2woZnrDRChRsYOzhJvk3ZXJMDrIcAWgRhbeIC1EkV9u9U2x9X269hvJK9Vfa5MYMVxo8vQDXL-KLfHHiwZna5et0ZOpK4k?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073897,-11.8483011,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(12,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzo6dR8m17cW-nazjLAmPbhHLQnoojpPvz6h940l7A9rQmLGs9t1coiWt3DlRLhGGQPBYBx96wUaVJ5E7mLdKgkCKlc-xI1xzJ1yM8z4sWIqwKYOCbj5_rjI-4E4Zb3G_xbx6_WHJ-SDBBIZnV6Ci7-V0KQtUCyIANekbL9-nB2c82wSJOyJSoPaaf5rGX0fADaDM5jo1Bq4m7Yqs9EIBjEYR14MhcOLY6FzDWpJsxdVaPMsx4i-iXq6dA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - SANGRADO - ASU 12 HILOS - POTENCIA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwYEG_M55daI5Bt85sNSAYAwiRfz3XuOYtv5-gF0Y__UtLr4mZWKWBdffltuiRkpQZv0bVzuWm7oAgDzxa75psPCTeqlB_wFSFH3u3rSww-qGVWAuWJSxS0TkJZPrA26mKFiFbnqf0cmU_hfoknqhVThQjlF1TAJzQnIiaZkD3BflUDu8_Fi1yGw4Zn40OHrBQn4O49tShDwTzJxshPtNukngw30tE1K5DgVQirFB2XlntZWOhHTWfBrVM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzf9B6IjbaSJo-IQKILIFHNrJCB3-sDLPQcHxmosU0QE_3WSnTfnoDpkjekcFHZlclF_nh_sKSGYeSYiVQTfPBovDZzLxh-sQ3wHzG4cxWBohNSMTlTvPBKvV7z2YAGDH9mU8sV_-d_jaJpRZRsVoR3DGSk1Q9akvwmZL3eQTypod5kGWMvGseDN95n4VLittlG390fasPWiiCBD3ILpOCPml8cgJa665nMekzmFg6Ljl1Z79_TRuvmIkg?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxP-PJMmYreI_XH3wRP9tSr1C8ZB8W0Vu5-dy4gW-Xwf_9D47QRprGHiLGSKbpu6Ktkm47bfcA-3NXXGheFg6RJSftSICszG81cmxztvG4i5ryJPGN9miXIXgUYcqOnjphhHIHM8-aBQZ7QeKTKSXc9rBB1LboeDTn5ET5y-yC-ldbIv-C-D62Lrl9YRtXbxKiFq7tqrhX7fYShh-CBpDcKiNQn1zEifxYitoN-4aw137gWmdCdIAsk624?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1072492,-11.8486635,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(13,1,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwq0abaMggn5p7A3aGZxexuo3w7845mn4WxKfS8vXS6WOOsTjvO8Jqd3j3-1lfzzXQtE4WQ0lv8iu3NM25TO3__3TU18kGHsXYmceeqEnYdJLPjM5_kV3TYgXEoiJf5qQ6DDwWcPZnMm3I4_l2RqX6vtHOES4vcDdE_cKA6Ad6EQ-xSEfPaeLoIikZlHFC8Oq2QliZmv0SgongzrPX7gbkk-UemxQ4rXKsExx8V1AS2D6j7_KWebQhMclo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA- SANGRADO - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzuQO8ma1ea46zvtpeqCHZ6WfyKJhH5QDFWQJhiz8kFxXA0pWO1k_v0OF723s5rqhWO8yQb0UAqxtQEKRQOb5itZAw6h3RMBPyBRAXxHc_am7GDt2BaMiAfmYjzAOqakmLWaDzexwBrM2vpNkNaHjuph5_B2brhlbpP9GQ9Ns4Uq865tR60YWSeu_cxyuqfX-td7iqJKk_xg0_D-vBoXy7bIg_Q6dvSYad9DC6BF_ynrpPikro23-jH3EI?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyqKLOig_zSbkdYEvm2CFBySjXo1tqLHjawi-nWye14aGqan7SB--Bg6CznD-9yr3nS4yY6KrDH_Ni7R-yzC9N5y96L3Ka0ZtQh_1d0We6scg3rvbfUgQW4i7PqR8jOGNsghPlJPBfv2AAmyFJzkFSobkBiAZUGUqZJsqB2ZiJbQoj108_kBfPadje58AtTtXfCB1_s7u_EaEAvO9nVwYbdIIkQtm1wE0iL48urNGdwanp_n0m0V0L70eE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxlCDRBg2h7a4r4oTjbWmwR2yQGEMf0QAXmNf59rO039gJHKHY1-8oHaC1OjdGXl_4yI7m3YM6ydYPvqb0_ZtT6uYfLnuaHr2zklf9V8ilTvDp4ELqFf7himPDfRK0gkkT24BEc-4lbdWZYZS_DeUxg3GjgphWGCj7-9GudaPH6gUbUOX3lOkymXFm46xDrg9auZf9ANwJm-pr-29gqIOU6TzJLEKFQn9F-50syNbML-IpUfMaIHcVDloM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073076,-11.8489193,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(14,1,'Point','CAJA NAP 8','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwjKtnuu72Rrow6KfrZq6rEq18fPaOEksxIyrJY98f8WPR5fhNctncRYMyn9F_N8ogC9yYOH6-WuLxmn5-X9xyaqJUBsCom7sOInrvABPretZJp73k4f_uPhmt_oHQKK8dCrLv4-i9t4muUoDRw1Uran8Y36cgtPJADJthT4S22LjwwYqaAqPV7K50hBr_OlWeQtvKou1XoLI7FMuCObcw-T6jZNhEhrXvpIpWDFbnqD-O-pPxEoq907rk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gx2FIPG_Sg4v9cSpEWCyFhxJegAHr4Gvsz1N9WLtWsn4iiLI7S4J-czAVqaUDkY_VsfkBACMRpnfEpLZXlvPPALwcqI2qNroq7fDDzvrH8s4Z0NUKGI11TaMCOtD_rncfk1jsAcMBSo-79iNZcBpqZ4_jgfwM034gEo1wYocI3w3otq0BsfIGQnOYv5EubLuSvFA61e9WDDs0gFP0I7cpNak1bjfbHAf4myWkpec2r8uEfT8l3jGUcNkIk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzv4VY7bScCc_UPxQw_uWtcvfUjXjT_LxgvpQ8UVrn5cUtv1SzgwJ0ajapuF3037GChC2-rx7GSd-UgCkD3t7dNboG3xfB8WUWQhyY4UQsQVihkWVl9KhDWj4txvHwjrZhNdaqi1j0SO1YlKc9dMq_zyjcEURAlrCwxepPi-ojHu7SX0Gjf4B-YR0H3tz7_ZrHLG97NvYz3snVg_a1cLJ9JrdWn5h7Ke7T-YAQ1fG-aA3C_OdNyE2edbOQ?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gw8PFxFU2pvKg5eyk0e9vQzsRr9BzeIzLnuSRlW17tBlH455QAiH-dnww15zr-SBxcaAdsKiTWoUISFXnhpnlo_Zcs42NLbIN_R_Cf_4U2nlTTFgv-Wc-cX875_W2DElf9Hd3YkmN0x0vKm_gYNuuXVAyGVnj5sK9iFW_vfCzxuQYquyO2CrMGlSZ-BgTfYQpux3FMw7g9UOLrrK2QOzd3ZuibWm3Xd7oaiBb5w59V89HdngKSRRVtRlHI?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.106729,-11.8486018,0]}','','','2026-06-03 01:43:15',0,'','',''),(15,1,'Point','CAJA NAP 8','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwIiZ3h_fXCsktv0nwtomuvWr9i3useMYNTU3Qe48aJBsVNRxREBC1uhsP26tgqUHMEE7MsOhsyX579zfE8b2O0XStOnGrijvao8atmIjgL6G9A88pIW-61sb3jfIQz4bhvS8_LVkLzr4o-x1TNAqT3Yp8J3yu88RpKlIhg_jN3PSmkvyBtkaEuROtcx7_cTcooQL6cGEWYFoHlqh1yPNiOGlcrb4IY39ZG4HiY5BlCwapeZnOMUYaZUzg?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL -SANGRADO - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxdlj7uPAig2irvWExHcO10ZOws36egb8Xz10vV44AVMAV2NL0FP1sS_f97OxlGnqqxDSbr5NN_vnOkXL5rrGYWYS3QkgZ-i_M1X3koLbHFWqlc-C49L2OXj8vpucaCzA3yRUYFw-qFiTRDZ4xoHWA45IBxQ_MrKWkDqDznmdhuQDW1j8qHlU8YB0aUyFyu2mpWFtPGeE6MPeP0ZGKjZiPfmRKF_Wi_4KWWfDToe5_KnGAEydW1tSCHgic?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzrkJ3NIZvNEULeUhK5TKkq2g5zZHlfkAcUtjw6PIJICyI878tpEtAZW_MmeRTzyBbaP8vNkPMQqA-SGQAs_4l6yAZq5Giuj0Ev4CPt0tM-DO90bQYQUwa0eKJj6oi2njR7DNu3F2c19iY3-uY8RtgNh_Zw_SMQeZcYXEHfwyeX657I9o5ta-IAItD7ZRWeww2uhcaujyi8NUMxKR3IvMHq7prMo-EFO9b7DbeQJG7V2xbo9xnQLY30Te0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gyljn0Lx5h4TdZUJvekspsslcGBVr_S8nruQMr9avxxFJnuzBETVkAS_v_EUGp6481gJMucWlQ1-CztzuSpbEPW4QkGv77YdTDyi-_1dWOKstYKXUjZawvfxCIGBqYjJ_clBle5ZN2OGHIgwUwDX9TuGEto48cbQReUM7fFFtC6RU-Fjy1cWtFS8Nj40X4ppI8vDKGxn_pPKu0zHwkznZ8vxW1O601-dh6gubtM5eGqS7ECiSctYZujWUs?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1074543,-11.847864,0]}','#facc15',NULL,'2026-06-03 01:43:15',0,'','',''),(16,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1074803,-11.8483447,0],[-77.1073153,-11.8486952,0],[-77.1072492,-11.8486635,0],[-77.1073153,-11.8486965,0],[-77.1072228,-11.8488855,0],[-77.1073117,-11.8489255,0],[-77.1067225,-11.8486663,0],[-77.106729,-11.8486018,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(17,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1075816,-11.8479201,0],[-77.1074543,-11.847864,0],[-77.1075816,-11.8479187,0],[-77.1075743,-11.8478433,0],[-77.1076876,-11.8475947,0],[-77.1078046,-11.8475511,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(18,1,'Point','MUFA PRINCIPAL','','{\"type\":\"Point\",\"coordinates\":[-77.1082255,-11.8486869,0]}','#ef4444',NULL,'2026-06-03 01:43:15',0,'','',''),(19,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085937,-11.8488589,0],[-77.1085736,-11.8488484,0],[-77.1084441,-11.8490856,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(20,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.108182,-11.848666,0],[-77.1080505,-11.8489482,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(21,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085736,-11.8488484,0],[-77.1086242,-11.8488073,0],[-77.1092679,-11.8491092,0],[-77.109858,-11.8493717,0],[-77.1097796,-11.849518,0],[-77.1100082,-11.8490488,0],[-77.1100538,-11.8490632,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(22,1,'Point','Punto 22','','{\"type\":\"Point\",\"coordinates\":[-77.1016017,-11.8477106,0]}','#a78bfa',NULL,'2026-06-03 01:43:15',0,'','',''),(23,1,'Point','Punto 23','','{\"type\":\"Point\",\"coordinates\":[-77.1010109,-11.8471751,0]}','#a78bfa',NULL,'2026-06-03 01:43:15',0,'','',''),(24,1,'Point','Punto 24','','{\"type\":\"Point\",\"coordinates\":[-77.1001958,-11.8466474,0]}','#a78bfa',NULL,'2026-06-03 01:43:15',0,'','',''),(25,1,'Point','Punto 25','','{\"type\":\"Point\",\"coordinates\":[-77.0994287,-11.8460857,0]}','#a78bfa',NULL,'2026-06-03 01:43:15',0,'','',''),(27,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1104289,-11.8497406,0],[-77.109838,-11.8494319,0],[-77.1097904,-11.849518,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(28,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1091457,-11.8486418,0],[-77.1094349,-11.848776,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(29,1,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1087664,-11.8492807,0],[-77.1089288,-11.8490223,0],[-77.1092359,-11.8491772,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 01:43:15',0,'','',''),(30,1,'LineString','Cable Principal','','{\"coordinates\":[[-77.10646544702446,-11.847457854128905],[-77.10485139334226,-11.846565736553728],[-77.10453206617449,-11.847008954567102],[-77.10625643287837,-11.847969257794134],[-77.10647705892177,-11.847457854128905],[-77.10663222421928,-11.846922361677372],[-77.10641753648021,-11.846533439097783],[-77.1064721693161,-11.846234405047156],[-77.10486937319308,-11.845448698479615]],\"type\":\"LineString\"}','#a78bfa','ph-map-pin','2026-06-03 02:11:05',0,'','',''),(31,1,'Point','Caja de Prueba 001','','{\"coordinates\":[-77.10453404685542,-11.847000425372855],\"type\":\"Point\"}','#a78bfa','ph-lightning','2026-06-03 02:11:16',0,'','',''),(32,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwvJJx_3hfSABXMiupIiy1hJ3Nfy8dJJ6uCEj2DeMUtmOi7E74HXjwaqgDtw7khltL_3koSCU4wJSvou7VojjPSok580eF5cJ-DNK8m7MOuSqLUXSi8DKwuxfq9XSl029c-Yr2Wb1T8BU04cDq_EisqC_NBBvicBuxtWh5P1AyY36_lDVAxosaWXjKQ0ne1i6LxhDlB8Y65b7VPRoBGwucMe2QmcWwELhErJ2Xd07vfVRiyplcri2ChMLk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwYFZyRvrtBB1AuBGwIxYRBflG1Z_MCnChabxF9G4Sz4g7crnxkLTkGwiCS2ZpRZ5pL75qDpVbNqDpbuYJqi6yJTfXQ5Hb7uHQ2lNWFn7EPSzXUw2ZSaNLppkVyjNZnWm276IcN6IslOmJWKKHMvIbg62cF-14cYY337PhJiZHUDUynFBPXUqmddFGGPTDSdmPqufohcG0lSq1ErEy2q87ihlWgdVJCJ6qKVbSs6l4o68AgbqhwTULAgbU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxte7v6X-AV5l46xB0KzMUz57-KHG_97igZE7NTGvTReQrsKPjrwPC4G3_Pd5c57B0xGirIsJpbP4nE85YnVpuy3JbbgWmJz_g78NxBNEZjJDcSG22SNkc4wUnmAFSKUOyuqPYh_KCh9iHJX9Ao-wqoFFJBtv0V7GRJaMrFtMEXEqRyt_aID8mGAAzrTJ0C4kVP735MGakKqdRpzs659NVI5pB7XGnp6d3bLOBMzU9DHdfwCiO1iJnnZQA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1078046,-11.8475511,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(33,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxuj_Rniuu-mxUPdGSqbHWKLCihP_1hI5HXqWJ0uCPEwzLM7dbJnYbb2PmuaqvAzFGxD7SRiYOvjhRwOl_dbHkhhTZjTFsVyZSkvYAwS3Of4Mnyl4t2X6VDCv9-Gfu_DQXR6Y3mDeWbGIysxUU7-Xpoy5YQVjOmhrFp7zgCh6U-jotQQ-r6zTk6caU_J7C0zN4997wCS8nd9y3ImLWnADb5gN7Cg_NigFsBUH_mjhMt0MN02T8AVu8q0r4?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO GRIS - ASU 12 HILOS - TERMINA EN PUNTA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyCvyYKg-GK4vUa5jxUd3bmo6kK_nyYQxhhmNYd0oH9Cq1Qmxw8Of09wB4Nd1g-kDg2Zs1m7DRus0IG3Qd51tqTnuQArTJ57M_XEr5bjNPHBEz8XR2OYFYy5ZRc7RfQZw-VFIrsA2bIly4LAPd6U3tVfB-v8yz2-LYBScPgr-YuVlpndxREMawk-NMiq9P1Y1rxJoI2wCDovcoNyUraAOdqjIyMiDNHdvbu5zwuICcTChff2uRHVnBTQWk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1100538,-11.8490632,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(34,2,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwf8lHqOaRYsb7JjnyE7P0fTMlIf9hkX8B-JnR5s34ZHKGwh2z7VGif_zJ2JzsBCrNWRGdQPEes-vXyve25HOfL6MkoJ2HjSbDTYaQS1UtU4mliTWjLtL7BCKMqPxnujVUwzvOFxbm_bBXm2yMgiBfN-unxAl_A5Bx0hsYuowtuA48Zi24-Q93zL5qNvwpkVmlPQWieuITKzQRgk8ZSDIbzV-EqTs0-lKHWdNc8ooSpsG_UD-G6MkQAgS0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - ASU 12 HILOS - SANGRADO - ENTRA UNA PUNTA Y ESTA FUSIONADO CON HILO MARRON Y AZUL','{\"type\":\"Point\",\"coordinates\":[-77.1097904,-11.849518,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(35,2,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5aTDJ7wuN1dnKiCKiJ3bHEKQQgb9SoGiDgDlsUpjzoYlg06jNN9YyLsO5UGvPJrW3iK1nWppXVTdd_ezRyXhEAuWrWl8rEqzshl4Rn65vYDmboCFA8FpMCxAwsj5Zl4DLU1V9IJWWYlOn5n6cbDUf2HM9XEoUMiKSHQlrvYCPZM5Uxx1TEXPZHwv36aHTwEBiMAcDRURsSWIir0wTH2NycyKtXyJFIhxGvcvO-I2zZq722HCqHJGa8NU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS -TERMINA EN PUNTA','{\"type\":\"Point\",\"coordinates\":[-77.1104302,-11.849734,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(36,2,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GweJJWX8WjtIjiwDqe4IWc2dSyjXC0tkN4U0mDiNfJK4sKpMg0DcP1FF-Xga-WpXSTxK5LHe2iIrpS8D0Q9rBLgezTWTIOk9_Xk73GkUpIMJ8QMmLXk-_qLW8kXmhgnxjviJ2PMN9JTzP9VSb1351Ulr6rXGKI5IfVY3GilB09hFYiI9h5A3XGj6nD-Q0e1TBzYJg6UW0emECeji2aVxG4A908dWSd0S7iosVHQ0lqWzWhJZ3qLWz8fYwA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA - POTENCIA -22 / ASU 12 HILOS - SANGRADO - ENTRA CABLE ASU DE 12 HILOS PUNTA LA FUCION HILO AZUL HILO BLANCO<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwcQkxSiMXsRNyKtqLtY67Doi-vRchhScI7Eub2yzigztUrFrPp02LFSlsyd_0CZ43TrEd_XDNEwDfDvSUsMj9Iwlncu8LXny1U2yvWvLm10bTbVMM_fhqiN9mg7cPWZSJydRcyOSEZiTtenNisHJCFC-qIG4pULMuF9AjceAfMMy4kxqNiBPnOU5fx-Axa2DdFymFv5lXJBaQLwRBco_jl-a12jbmdRqOMk8iZaN8o1Gzs4srRGWqix1c?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwUpJkx9w3OuBCTEKPg-VRhdbwRlyZepba8fonZTdgzcc1HQYZud2QPdq4vw3VHPTzoF3KJMSwSefA2-QxTWSkz1R_fJ8aYKyv_TkxkK-P-rfghTXiW9Lz1jrJpZw9bOzfHKpjGAecj3bj-SFaJHa55Mq5KkWoylhM1hUArnxhGS_Au27VZFi39CNU6kkFy7TJk7BoHIwHhBKyfS2ybpAeQmuGaATOhXtcmqtrfzXvM58T59mhbK0vaPbc?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1092775,-11.8490866,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(37,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzAw7bwcJjXrlnS58bzxMT58xOpoltkQ8qZOTy_jcz7Vf1zIq9vmjrtfYPFS_x7pbB8I20MGbabfEKJR_GolLcC0z0NBwyxn6p53-XPF5t8jRQrxcR8afbB6NBIp_tZOVHgA9NUJs-zKqZxhmVmiqvvKtCoHTEge87KEzXLcU9BDE2Yt5WhTCzbhBufiHeRVvPOYCYLC5aaLEv6Xm3vbWIpVHH10VjY9zvqOg4wc9NXQGLOK6QxekV7K0I?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - POTENCIA -23  /  TERMINA EN PUNTA -CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwLrnDuL54DzvUKj26vOlxrubBj2gL1E9y8vacuhX_FjF51CSnZehzVg8mdsZ2f-BLmScAtCuesCN9W4UIJ_oUs_0ODbWCVsGkl6b5WwRLuhTAJAbmshz5SlpYeFeKUK9ZUCEurUjA3Dh6PvHudhnT7yN--a9HdP97kgA3XUhxivDpmmyJSEZSbDwAKyFVcSa1iiECQNKWSqUBmvcht45zUJffAE7a1Pw1ju1KNF27s0THuRhoHjT66xjo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz6Ida-rcnqvl8hpDalIdu4-4y1aHpE2jPjob8RdxbagumdmstlBW1kWLGGlq1VsyEoUDPPV2OXI9-BC5IGNGBWlNUT-DBUcbWd_4lWw2NRhNtoCunomF78aNafEO0sNgu3txgWUJXjIki2IiIjIBz7qx1GkerlehQoQuJfSj5VE7pjamGzuUNCfNRF1P-qxQhfI5lU1JvQorpxzJB7O5PoVJPdjdE5xPZiAapk_K8gYE3SlPiMIHr63WE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzZy4dH8Dx_j6ezYrz52xKgDZoH3xAhiW89Al2rM1mooF_2y-w_c7HEOHeFVSvL3rt-HfxMONUZq45W1gHK9EptNRR49musWKHkYpBXVZNbf-DdB2k6JgE1u_UxdXUCTWgKQamMrbTlHGqK2p8dzP7qOS5PBdjQ6PbkTQnRAEZhB0bkx-i1nukgg3YpcTE5XCPwd0kfVhkX0RyiFzTXvda3kDICddKbsMDwCsXTaTX4s-9poldIvyk3nfk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1091457,-11.8486418,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(38,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzkFtlVbB6VD8okBkta91Y1aJ3vF9-Ldbn3Gvm4Q1IJStYC7OpJV3QokPMwykjOvrE2kP4UcNJyXdXoonwkZMhk7H5gsHshkacaopK9x_fVMXJOtoey9YFxXGWyLPiU9WNHiYw83rvrHPzYbKCSEftYOqdIfLJdugsHnke9uQyN8CtckZUsgNrzP850mBmcsJFAVYw0WmodQlmpk-xRXVOuHs64PG2m7FoAH6VZqcAnPpIa4GV3FvQU7IM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS - POTENCIA -24<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyC1hbrPTCv5f6YFbzTEGyOtm_hDc3MzEc6NkH69oYEWmqZMjAgSEd27ogCL_7OFdm0HdTU3Nt9nAbOIcyBIWjTGTVZUG2WV90tRoY6m1buhJoYSXjMgErqWXdQEB3uPkoledv5_8XhU83TsaZJ8zRjTlEYTFnKZFsVrkqcAV1trJj8Yc5Yv9223FFdhj0o-BHaSx3bdanGPrd2TWV5K4K6ouqyL5fnj9-5xN8GMpEaZ-15uznigmIm-wA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxmRr1xRCqetaacJrwrB9TBO1JjKfXEk3MD8HQmPTaw4-zfeVtztU6UJbBFXqOnE4H1EEjABaBzv9bVxlIm5XIOo-kMD2fWhvnGBcY6eCB6-tR6x26xIoGXLso_V58ztdLsaI5LuQPa4RAK77tgn3Heamvpqv8PhravRqGQ97JiN2hGnmjblwRAPmDvSldcSTjqkPfwRlDJ4ACm6_oSyemrGNgNN_68YljGdbPWA2Gh_oq9r_EhxsWoJkM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GySsJmVO32CiOhOL_TId7k1hobPOsm035nRzsMzF_CEaNreRZpQtRi8nKR00ny79t5MI2bZvaMRrU5mqEFM1wh_pu-fQsyAya-YyTk4fOyPIB4b6pNq7wtoP01bPCs1FR1KxHiftzjkUk6M8ta-zVNrJlAhCtMyEQ1Sy0PvyVga2oNotZRmr_1TV1dnB635eTmuu2shQS9bvO-LV0ervW81KGwZjT2ppgqjsJpi8V3tHuRy9nnbwhIEoAo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1087664,-11.8492807,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(39,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzcCIzEJs9zPSfnpLCCsxe_AhH8jLcQ7RzLjUo3C_s9qnqvAtNSc973Hd6K2EVyqJ_PTmSYvy3fpCLFTX-XqyOqF0_NRIbUYeZjHn1gAPMXT7BES6_gDxlAJO-hxpxr3dcD_y5oRim1iW2JNAdYxAX9RzrnmXsgAKGnx3sQcbWUpCsx801fHXYHIPQEyrr85QWPuXYGhYTt7wjkI_Gf_hWn7uSsnvgueUF0y4Y4nbKJa4-vcLqm3CSR1DY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwnqACqik_sjYpbTDgNuv1EmNGFXzpwR-ct6Udz6UwQUVjc_Fu0inYiuq0LR774NN44QR90rjrVS9czyorP-u-LTaJOQpCpOHfHUnPGbmkGmiZwxf5jrSSELgLTqSZMSPgVOZ4G1IGmGyxNN4WTQeD2NQ9sREEcPcUhWvSbH1_LSXauzrUWxNUtCBKpdYEn7rjJWNs1nyANN_A5om3TD_QOb3g12kLHxNWan9utRxzmOQrDwKg9Fw0kYkU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywDDG8oprKaantfesm-XqoVOjQRcVd-_gWwZqJpnMQO74uOSUvjnajtZs1cu9y4KKWYVREWlAE7SRPwkcEghJsrtxmfwpzNb3RncWPrt_g-lOjj3JtG7e5y12-03sYl_0lkjr8RN4xt3DOk8gmC_-4lpPtO7AzCx1L1cK4xmjEnRkkxw5vbkhcx8LBvI6hUN_2WYLvBROJ5xz7yypHVEdp7Q3hXzwDMpjylO7yh43IbJXAAJahNzkta90?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1084441,-11.8490856,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(40,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAl_37pvf4_hp9jSJ_PYeq_ge8KWQz3DNC_KyyvJVvKq8z9n2nxauhm6sdyg48jTcdqf0yDAd2It5BKsGLdgv43mecQ9E89PlCOyp-0Zieof_UCBmppjuOihBc_D8fe1-Q7d8M9OSVTlq2rtrd3YDZU4rheaqNmP-CkKrzsA1PbD9L2NivvI1jvcuYAAZ0DLylwZGcO5v5PTExJyvjDK1G1l9quEeBdpolmKC5dqv0_5Y6QesSWxhU6vY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA <br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywsJ0GMED71uL-UypU2Ko7E9iMXvyw9rLy3KV3ocd9w2WfZ_VMSKluDefyEv_ng5JoAyXSGII4lwHouaOIn6OU7yqav8oHX2tgydY1khX1wiiKCvBlfdIhwERhnEGBYyU8TjjdmaTx6eovmEZDbo-cSCFgQJOXCNfEUkTUm5cmNm6DW1J4PYgwB3x6m2UF4dNjdc5xiuPIfyythTqUcL72liBtQaKKX6H9uV0ZQ4SQT-uKIuxdupVCny0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1080508,-11.8489455,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(41,2,'Point','MUFA PEQUEÑA','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwqwx-GtnzHdDOPa6HgtgBo1rfEaigxj-sFnz4xxWfgO2KUa6DHuJ6irMkndYk_pX2nIXgTU-o8i2GvMpqEjKkFzBPrxkZhT5NWTinjWAmTb9_VS93aJedoduQd8zhJgBx5ts-gCDYyF_uISsltcPv_tUMZKDYEYqojXQtu-NIwA_6rhl9XLeZQBnaIcC0c_fuN6-Q4YbCtVPRG8DAerPuCeBga1ym5BS1HItu0pFe_d8H-7rh0GQP6CVE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz_mHzIFYaoSsrW9DfhKJc44NJFuA1xrpE1nr3sE56TKAnK22vXbR3r2heNNez8Lu2ZePNss17rZJyE7wCiEf4eUWBJKHeU5SMQVmpF-r9XRir_IXMKITa4ctnpwkvHvLa_yxeOsCsVq_vavrIhGRiP8ctFVVK70jW9b5FjTlrrShsZDMMokeYP3K_TIF-DZucLDMSGJYu1H4g7WkxYdVTaXxfhBgghBeUOVbqXcVCUXcve0BjvfrDCKl8?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyttNLZrVzgET0_Kzrq0_6koEr0vLgTxiY3uPGRJfV2pJpryRNMC-62rfng_j87s2unjcIE8q24uakF-v_AGMS9k0-IlPo1jkEtViTpXjvxdqTbVd_oqZ9YliXtmC2hEkGEczhSIQ8y10XWW6o8s5fvA2Co4vOo7XQgu_G6Z18gYdczLP0Re8QGeA-xR56zU_ZCtN_obdnemyL1oeUd-EkPluywbDS6AaGHMvyR_IYXN1-xs-2Hm_YtvAw?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1066682,-11.847964,0]}','#fb923c',NULL,'2026-06-03 02:12:43',0,'','',''),(42,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxMmjp5Wf8yHrPAnQ1NzpGq6J9pFUPmz6DuBYJxyVVXdX5wPZ0LqUMZ8Alpzq1K4Zy9ZXMRdAIV5r19DHWjmQC4MGpGAUmCtdzoxyKO0oLmLgSm-Ro9KdwCxKpp86EkkoVheEUyRCqPxCfarqga10_tSpscMM9MHwN4vdsAyEQP-QxFbRJAakqrsdpqrN9Lkmtp8lUrDwsZ8a5inZkhGZ30QDhbw-9I3L1S_iB69nvA6kf_zK3w6WxRX1Y?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS - EN ESTA CAJA ENCONTRAMOS UN SANGRADO Y 2 PUNTAS ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5r9VlmLri6xPKvzCuCRc1X_Djp0VFmOojvRpgJdbIO16-QRZcGKDIINkIKU3TseUgjNKvbLr7GRqbDc5N4DSRB_-RUd7njsnMpOYOoB-HBj5MCFRDEeu9ZgohiG0WMgnJDJWANOE6ZYSlTOjzAogyEgUE7S14dCcJIif4IwSNipz-UDA7qQfQdtp0maceXnr_vmH6f5GDZVMxvBrkLs2T6jxCkHSnohxD1TuuZERH99UJpGjePMY0470?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gw3eZB35BFpLfZ7fkk-chagzEwMmaHG73dr-uFdvDeBw2DwCCBeJRHdo0BriN-6KTtmAq-dkVt60RIht6-ixhApIKqHAdFdSwy6ZXHgORnqagp2XfE6ply0CT65TgA704bzDPYUHVfei3lRFnHAB2ziNxM3Yn_rR_LDPNiSMYCysM1ln2Z5uOUul6wnro66Wf7AtqVhF11jm_sAA4kFBJ0-UnPNahsZbGTWzoq2KCctAW3I0dKP7iQLIfQ?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAa4c6R0OBVXg4Vp6Skdvq5WyGwrFnHtQIk4MZ_6UHUmtFLqmgpzKZ0WZQvkvnnB3yi2aYZmdvjVjzTZSGd-VHtRTuFeEY7wmdFORwZWz1L7csgqranwSgxmRxAWcLpfZ0sBMxWYJgu4XDq4ZqQmIBfKyokyH39qwfkZN_VCHgK1DWgl62rEWcSyRoWskCOAzyZpCv4eVyVC1rUELB885rmnzWC7BvxSUbMkRWMk7Xc-UN1pTI5AYjplk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwVS6O4aF8Q35BmCAJIwh7EAKhf_NNIs_BGgWOnn0ONzAE3ijHUVXaTOn5WkXPTvmF4mF9DcM_tLCnngSc-a-jmoKYJEkPf2Wv6wB07fOcLq9-ojz39XyIF09tx7ieRuFUPoDelm21YdsbSCjL3VY3ix9dLC2woZnrDRChRsYOzhJvk3ZXJMDrIcAWgRhbeIC1EkV9u9U2x9X269hvJK9Vfa5MYMVxo8vQDXL-KLfHHiwZna5et0ZOpK4k?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073897,-11.8483011,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(43,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzo6dR8m17cW-nazjLAmPbhHLQnoojpPvz6h940l7A9rQmLGs9t1coiWt3DlRLhGGQPBYBx96wUaVJ5E7mLdKgkCKlc-xI1xzJ1yM8z4sWIqwKYOCbj5_rjI-4E4Zb3G_xbx6_WHJ-SDBBIZnV6Ci7-V0KQtUCyIANekbL9-nB2c82wSJOyJSoPaaf5rGX0fADaDM5jo1Bq4m7Yqs9EIBjEYR14MhcOLY6FzDWpJsxdVaPMsx4i-iXq6dA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - SANGRADO - ASU 12 HILOS - POTENCIA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwYEG_M55daI5Bt85sNSAYAwiRfz3XuOYtv5-gF0Y__UtLr4mZWKWBdffltuiRkpQZv0bVzuWm7oAgDzxa75psPCTeqlB_wFSFH3u3rSww-qGVWAuWJSxS0TkJZPrA26mKFiFbnqf0cmU_hfoknqhVThQjlF1TAJzQnIiaZkD3BflUDu8_Fi1yGw4Zn40OHrBQn4O49tShDwTzJxshPtNukngw30tE1K5DgVQirFB2XlntZWOhHTWfBrVM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzf9B6IjbaSJo-IQKILIFHNrJCB3-sDLPQcHxmosU0QE_3WSnTfnoDpkjekcFHZlclF_nh_sKSGYeSYiVQTfPBovDZzLxh-sQ3wHzG4cxWBohNSMTlTvPBKvV7z2YAGDH9mU8sV_-d_jaJpRZRsVoR3DGSk1Q9akvwmZL3eQTypod5kGWMvGseDN95n4VLittlG390fasPWiiCBD3ILpOCPml8cgJa665nMekzmFg6Ljl1Z79_TRuvmIkg?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxP-PJMmYreI_XH3wRP9tSr1C8ZB8W0Vu5-dy4gW-Xwf_9D47QRprGHiLGSKbpu6Ktkm47bfcA-3NXXGheFg6RJSftSICszG81cmxztvG4i5ryJPGN9miXIXgUYcqOnjphhHIHM8-aBQZ7QeKTKSXc9rBB1LboeDTn5ET5y-yC-ldbIv-C-D62Lrl9YRtXbxKiFq7tqrhX7fYShh-CBpDcKiNQn1zEifxYitoN-4aw137gWmdCdIAsk624?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1072492,-11.8486635,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(44,2,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwq0abaMggn5p7A3aGZxexuo3w7845mn4WxKfS8vXS6WOOsTjvO8Jqd3j3-1lfzzXQtE4WQ0lv8iu3NM25TO3__3TU18kGHsXYmceeqEnYdJLPjM5_kV3TYgXEoiJf5qQ6DDwWcPZnMm3I4_l2RqX6vtHOES4vcDdE_cKA6Ad6EQ-xSEfPaeLoIikZlHFC8Oq2QliZmv0SgongzrPX7gbkk-UemxQ4rXKsExx8V1AS2D6j7_KWebQhMclo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA- SANGRADO - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzuQO8ma1ea46zvtpeqCHZ6WfyKJhH5QDFWQJhiz8kFxXA0pWO1k_v0OF723s5rqhWO8yQb0UAqxtQEKRQOb5itZAw6h3RMBPyBRAXxHc_am7GDt2BaMiAfmYjzAOqakmLWaDzexwBrM2vpNkNaHjuph5_B2brhlbpP9GQ9Ns4Uq865tR60YWSeu_cxyuqfX-td7iqJKk_xg0_D-vBoXy7bIg_Q6dvSYad9DC6BF_ynrpPikro23-jH3EI?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyqKLOig_zSbkdYEvm2CFBySjXo1tqLHjawi-nWye14aGqan7SB--Bg6CznD-9yr3nS4yY6KrDH_Ni7R-yzC9N5y96L3Ka0ZtQh_1d0We6scg3rvbfUgQW4i7PqR8jOGNsghPlJPBfv2AAmyFJzkFSobkBiAZUGUqZJsqB2ZiJbQoj108_kBfPadje58AtTtXfCB1_s7u_EaEAvO9nVwYbdIIkQtm1wE0iL48urNGdwanp_n0m0V0L70eE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxlCDRBg2h7a4r4oTjbWmwR2yQGEMf0QAXmNf59rO039gJHKHY1-8oHaC1OjdGXl_4yI7m3YM6ydYPvqb0_ZtT6uYfLnuaHr2zklf9V8ilTvDp4ELqFf7himPDfRK0gkkT24BEc-4lbdWZYZS_DeUxg3GjgphWGCj7-9GudaPH6gUbUOX3lOkymXFm46xDrg9auZf9ANwJm-pr-29gqIOU6TzJLEKFQn9F-50syNbML-IpUfMaIHcVDloM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073076,-11.8489193,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(45,2,'Point','CAJA NAP 8','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwjKtnuu72Rrow6KfrZq6rEq18fPaOEksxIyrJY98f8WPR5fhNctncRYMyn9F_N8ogC9yYOH6-WuLxmn5-X9xyaqJUBsCom7sOInrvABPretZJp73k4f_uPhmt_oHQKK8dCrLv4-i9t4muUoDRw1Uran8Y36cgtPJADJthT4S22LjwwYqaAqPV7K50hBr_OlWeQtvKou1XoLI7FMuCObcw-T6jZNhEhrXvpIpWDFbnqD-O-pPxEoq907rk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gx2FIPG_Sg4v9cSpEWCyFhxJegAHr4Gvsz1N9WLtWsn4iiLI7S4J-czAVqaUDkY_VsfkBACMRpnfEpLZXlvPPALwcqI2qNroq7fDDzvrH8s4Z0NUKGI11TaMCOtD_rncfk1jsAcMBSo-79iNZcBpqZ4_jgfwM034gEo1wYocI3w3otq0BsfIGQnOYv5EubLuSvFA61e9WDDs0gFP0I7cpNak1bjfbHAf4myWkpec2r8uEfT8l3jGUcNkIk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzv4VY7bScCc_UPxQw_uWtcvfUjXjT_LxgvpQ8UVrn5cUtv1SzgwJ0ajapuF3037GChC2-rx7GSd-UgCkD3t7dNboG3xfB8WUWQhyY4UQsQVihkWVl9KhDWj4txvHwjrZhNdaqi1j0SO1YlKc9dMq_zyjcEURAlrCwxepPi-ojHu7SX0Gjf4B-YR0H3tz7_ZrHLG97NvYz3snVg_a1cLJ9JrdWn5h7Ke7T-YAQ1fG-aA3C_OdNyE2edbOQ?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gw8PFxFU2pvKg5eyk0e9vQzsRr9BzeIzLnuSRlW17tBlH455QAiH-dnww15zr-SBxcaAdsKiTWoUISFXnhpnlo_Zcs42NLbIN_R_Cf_4U2nlTTFgv-Wc-cX875_W2DElf9Hd3YkmN0x0vKm_gYNuuXVAyGVnj5sK9iFW_vfCzxuQYquyO2CrMGlSZ-BgTfYQpux3FMw7g9UOLrrK2QOzd3ZuibWm3Xd7oaiBb5w59V89HdngKSRRVtRlHI?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.106729,-11.8486018,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(46,2,'Point','CAJA NAP 8','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwIiZ3h_fXCsktv0nwtomuvWr9i3useMYNTU3Qe48aJBsVNRxREBC1uhsP26tgqUHMEE7MsOhsyX579zfE8b2O0XStOnGrijvao8atmIjgL6G9A88pIW-61sb3jfIQz4bhvS8_LVkLzr4o-x1TNAqT3Yp8J3yu88RpKlIhg_jN3PSmkvyBtkaEuROtcx7_cTcooQL6cGEWYFoHlqh1yPNiOGlcrb4IY39ZG4HiY5BlCwapeZnOMUYaZUzg?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL -SANGRADO - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxdlj7uPAig2irvWExHcO10ZOws36egb8Xz10vV44AVMAV2NL0FP1sS_f97OxlGnqqxDSbr5NN_vnOkXL5rrGYWYS3QkgZ-i_M1X3koLbHFWqlc-C49L2OXj8vpucaCzA3yRUYFw-qFiTRDZ4xoHWA45IBxQ_MrKWkDqDznmdhuQDW1j8qHlU8YB0aUyFyu2mpWFtPGeE6MPeP0ZGKjZiPfmRKF_Wi_4KWWfDToe5_KnGAEydW1tSCHgic?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzrkJ3NIZvNEULeUhK5TKkq2g5zZHlfkAcUtjw6PIJICyI878tpEtAZW_MmeRTzyBbaP8vNkPMQqA-SGQAs_4l6yAZq5Giuj0Ev4CPt0tM-DO90bQYQUwa0eKJj6oi2njR7DNu3F2c19iY3-uY8RtgNh_Zw_SMQeZcYXEHfwyeX657I9o5ta-IAItD7ZRWeww2uhcaujyi8NUMxKR3IvMHq7prMo-EFO9b7DbeQJG7V2xbo9xnQLY30Te0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gyljn0Lx5h4TdZUJvekspsslcGBVr_S8nruQMr9avxxFJnuzBETVkAS_v_EUGp6481gJMucWlQ1-CztzuSpbEPW4QkGv77YdTDyi-_1dWOKstYKXUjZawvfxCIGBqYjJ_clBle5ZN2OGHIgwUwDX9TuGEto48cbQReUM7fFFtC6RU-Fjy1cWtFS8Nj40X4ppI8vDKGxn_pPKu0zHwkznZ8vxW1O601-dh6gubtM5eGqS7ECiSctYZujWUs?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1074543,-11.847864,0]}','#facc15',NULL,'2026-06-03 02:12:43',0,'','',''),(47,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1074803,-11.8483447,0],[-77.1073153,-11.8486952,0],[-77.1072492,-11.8486635,0],[-77.1073153,-11.8486965,0],[-77.1072228,-11.8488855,0],[-77.1073117,-11.8489255,0],[-77.1067225,-11.8486663,0],[-77.106729,-11.8486018,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(48,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1075816,-11.8479201,0],[-77.1074543,-11.847864,0],[-77.1075816,-11.8479187,0],[-77.1075743,-11.8478433,0],[-77.1076876,-11.8475947,0],[-77.1078046,-11.8475511,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(49,2,'Point','MUFA PRINCIPAL','','{\"type\":\"Point\",\"coordinates\":[-77.1082255,-11.8486869,0]}','#ef4444',NULL,'2026-06-03 02:12:43',0,'','',''),(50,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085937,-11.8488589,0],[-77.1085736,-11.8488484,0],[-77.1084441,-11.8490856,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(51,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.108182,-11.848666,0],[-77.1080505,-11.8489482,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(52,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085736,-11.8488484,0],[-77.1086242,-11.8488073,0],[-77.1092679,-11.8491092,0],[-77.109858,-11.8493717,0],[-77.1097796,-11.849518,0],[-77.1100082,-11.8490488,0],[-77.1100538,-11.8490632,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(53,2,'Point','Punto 22','','{\"type\":\"Point\",\"coordinates\":[-77.1016017,-11.8477106,0]}','#a78bfa',NULL,'2026-06-03 02:12:43',0,'','',''),(54,2,'Point','Punto 23','','{\"type\":\"Point\",\"coordinates\":[-77.1010109,-11.8471751,0]}','#a78bfa',NULL,'2026-06-03 02:12:43',0,'','',''),(55,2,'Point','Punto 24','','{\"type\":\"Point\",\"coordinates\":[-77.1001958,-11.8466474,0]}','#a78bfa',NULL,'2026-06-03 02:12:43',0,'','',''),(56,2,'Point','Punto 25','','{\"type\":\"Point\",\"coordinates\":[-77.0994287,-11.8460857,0]}','#a78bfa',NULL,'2026-06-03 02:12:43',0,'','',''),(57,2,'Polygon','zona','','{\"type\":\"Polygon\",\"coordinates\":[[[-77.1109445,-11.850463,0],[-77.1088416,-11.8506992,0],[-77.1084822,-11.849413,0],[-77.1109445,-11.850463,0]]]}','#a78bfa',NULL,'2026-06-03 02:12:43',0,'','',''),(58,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1104289,-11.8497406,0],[-77.109838,-11.8494319,0],[-77.1097904,-11.849518,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(59,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1091457,-11.8486418,0],[-77.1094349,-11.848776,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(60,2,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1087664,-11.8492807,0],[-77.1089288,-11.8490223,0],[-77.1092359,-11.8491772,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 02:12:43',0,'','',''),(61,3,'Point','CAJA NAP','','{\"type\":\"Point\",\"coordinates\":[-77.1078046,-11.8475511,0]}','#facc15','ph-map-pin','2026-06-03 02:13:32',0,'','',''),(62,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gxuj_Rniuu-mxUPdGSqbHWKLCihP_1hI5HXqWJ0uCPEwzLM7dbJnYbb2PmuaqvAzFGxD7SRiYOvjhRwOl_dbHkhhTZjTFsVyZSkvYAwS3Of4Mnyl4t2X6VDCv9-Gfu_DQXR6Y3mDeWbGIysxUU7-Xpoy5YQVjOmhrFp7zgCh6U-jotQQ-r6zTk6caU_J7C0zN4997wCS8nd9y3ImLWnADb5gN7Cg_NigFsBUH_mjhMt0MN02T8AVu8q0r4?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO GRIS - ASU 12 HILOS - TERMINA EN PUNTA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyCvyYKg-GK4vUa5jxUd3bmo6kK_nyYQxhhmNYd0oH9Cq1Qmxw8Of09wB4Nd1g-kDg2Zs1m7DRus0IG3Qd51tqTnuQArTJ57M_XEr5bjNPHBEz8XR2OYFYy5ZRc7RfQZw-VFIrsA2bIly4LAPd6U3tVfB-v8yz2-LYBScPgr-YuVlpndxREMawk-NMiq9P1Y1rxJoI2wCDovcoNyUraAOdqjIyMiDNHdvbu5zwuICcTChff2uRHVnBTQWk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1100538,-11.8490632,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(63,3,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwf8lHqOaRYsb7JjnyE7P0fTMlIf9hkX8B-JnR5s34ZHKGwh2z7VGif_zJ2JzsBCrNWRGdQPEes-vXyve25HOfL6MkoJ2HjSbDTYaQS1UtU4mliTWjLtL7BCKMqPxnujVUwzvOFxbm_bBXm2yMgiBfN-unxAl_A5Bx0hsYuowtuA48Zi24-Q93zL5qNvwpkVmlPQWieuITKzQRgk8ZSDIbzV-EqTs0-lKHWdNc8ooSpsG_UD-G6MkQAgS0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - ASU 12 HILOS - SANGRADO - ENTRA UNA PUNTA Y ESTA FUSIONADO CON HILO MARRON Y AZUL','{\"type\":\"Point\",\"coordinates\":[-77.1097904,-11.849518,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(64,3,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5aTDJ7wuN1dnKiCKiJ3bHEKQQgb9SoGiDgDlsUpjzoYlg06jNN9YyLsO5UGvPJrW3iK1nWppXVTdd_ezRyXhEAuWrWl8rEqzshl4Rn65vYDmboCFA8FpMCxAwsj5Zl4DLU1V9IJWWYlOn5n6cbDUf2HM9XEoUMiKSHQlrvYCPZM5Uxx1TEXPZHwv36aHTwEBiMAcDRURsSWIir0wTH2NycyKtXyJFIhxGvcvO-I2zZq722HCqHJGa8NU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS -TERMINA EN PUNTA','{\"type\":\"Point\",\"coordinates\":[-77.1104302,-11.849734,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(65,3,'Point','CAJA NAP ','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GweJJWX8WjtIjiwDqe4IWc2dSyjXC0tkN4U0mDiNfJK4sKpMg0DcP1FF-Xga-WpXSTxK5LHe2iIrpS8D0Q9rBLgezTWTIOk9_Xk73GkUpIMJ8QMmLXk-_qLW8kXmhgnxjviJ2PMN9JTzP9VSb1351Ulr6rXGKI5IfVY3GilB09hFYiI9h5A3XGj6nD-Q0e1TBzYJg6UW0emECeji2aVxG4A908dWSd0S7iosVHQ0lqWzWhJZ3qLWz8fYwA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA - POTENCIA -22 / ASU 12 HILOS - SANGRADO - ENTRA CABLE ASU DE 12 HILOS PUNTA LA FUCION HILO AZUL HILO BLANCO<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwcQkxSiMXsRNyKtqLtY67Doi-vRchhScI7Eub2yzigztUrFrPp02LFSlsyd_0CZ43TrEd_XDNEwDfDvSUsMj9Iwlncu8LXny1U2yvWvLm10bTbVMM_fhqiN9mg7cPWZSJydRcyOSEZiTtenNisHJCFC-qIG4pULMuF9AjceAfMMy4kxqNiBPnOU5fx-Axa2DdFymFv5lXJBaQLwRBco_jl-a12jbmdRqOMk8iZaN8o1Gzs4srRGWqix1c?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwUpJkx9w3OuBCTEKPg-VRhdbwRlyZepba8fonZTdgzcc1HQYZud2QPdq4vw3VHPTzoF3KJMSwSefA2-QxTWSkz1R_fJ8aYKyv_TkxkK-P-rfghTXiW9Lz1jrJpZw9bOzfHKpjGAecj3bj-SFaJHa55Mq5KkWoylhM1hUArnxhGS_Au27VZFi39CNU6kkFy7TJk7BoHIwHhBKyfS2ybpAeQmuGaATOhXtcmqtrfzXvM58T59mhbK0vaPbc?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1092775,-11.8490866,0]}','#38bdf8','ph-star','2026-06-03 02:13:32',0,'','',''),(66,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzAw7bwcJjXrlnS58bzxMT58xOpoltkQ8qZOTy_jcz7Vf1zIq9vmjrtfYPFS_x7pbB8I20MGbabfEKJR_GolLcC0z0NBwyxn6p53-XPF5t8jRQrxcR8afbB6NBIp_tZOVHgA9NUJs-zKqZxhmVmiqvvKtCoHTEge87KEzXLcU9BDE2Yt5WhTCzbhBufiHeRVvPOYCYLC5aaLEv6Xm3vbWIpVHH10VjY9zvqOg4wc9NXQGLOK6QxekV7K0I?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - POTENCIA -23  /  TERMINA EN PUNTA -CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwLrnDuL54DzvUKj26vOlxrubBj2gL1E9y8vacuhX_FjF51CSnZehzVg8mdsZ2f-BLmScAtCuesCN9W4UIJ_oUs_0ODbWCVsGkl6b5WwRLuhTAJAbmshz5SlpYeFeKUK9ZUCEurUjA3Dh6PvHudhnT7yN--a9HdP97kgA3XUhxivDpmmyJSEZSbDwAKyFVcSa1iiECQNKWSqUBmvcht45zUJffAE7a1Pw1ju1KNF27s0THuRhoHjT66xjo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz6Ida-rcnqvl8hpDalIdu4-4y1aHpE2jPjob8RdxbagumdmstlBW1kWLGGlq1VsyEoUDPPV2OXI9-BC5IGNGBWlNUT-DBUcbWd_4lWw2NRhNtoCunomF78aNafEO0sNgu3txgWUJXjIki2IiIjIBz7qx1GkerlehQoQuJfSj5VE7pjamGzuUNCfNRF1P-qxQhfI5lU1JvQorpxzJB7O5PoVJPdjdE5xPZiAapk_K8gYE3SlPiMIHr63WE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzZy4dH8Dx_j6ezYrz52xKgDZoH3xAhiW89Al2rM1mooF_2y-w_c7HEOHeFVSvL3rt-HfxMONUZq45W1gHK9EptNRR49musWKHkYpBXVZNbf-DdB2k6JgE1u_UxdXUCTWgKQamMrbTlHGqK2p8dzP7qOS5PBdjQ6PbkTQnRAEZhB0bkx-i1nukgg3YpcTE5XCPwd0kfVhkX0RyiFzTXvda3kDICddKbsMDwCsXTaTX4s-9poldIvyk3nfk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1091457,-11.8486418,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(67,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzkFtlVbB6VD8okBkta91Y1aJ3vF9-Ldbn3Gvm4Q1IJStYC7OpJV3QokPMwykjOvrE2kP4UcNJyXdXoonwkZMhk7H5gsHshkacaopK9x_fVMXJOtoey9YFxXGWyLPiU9WNHiYw83rvrHPzYbKCSEftYOqdIfLJdugsHnke9uQyN8CtckZUsgNrzP850mBmcsJFAVYw0WmodQlmpk-xRXVOuHs64PG2m7FoAH6VZqcAnPpIa4GV3FvQU7IM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS - POTENCIA -24<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyC1hbrPTCv5f6YFbzTEGyOtm_hDc3MzEc6NkH69oYEWmqZMjAgSEd27ogCL_7OFdm0HdTU3Nt9nAbOIcyBIWjTGTVZUG2WV90tRoY6m1buhJoYSXjMgErqWXdQEB3uPkoledv5_8XhU83TsaZJ8zRjTlEYTFnKZFsVrkqcAV1trJj8Yc5Yv9223FFdhj0o-BHaSx3bdanGPrd2TWV5K4K6ouqyL5fnj9-5xN8GMpEaZ-15uznigmIm-wA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxmRr1xRCqetaacJrwrB9TBO1JjKfXEk3MD8HQmPTaw4-zfeVtztU6UJbBFXqOnE4H1EEjABaBzv9bVxlIm5XIOo-kMD2fWhvnGBcY6eCB6-tR6x26xIoGXLso_V58ztdLsaI5LuQPa4RAK77tgn3Heamvpqv8PhravRqGQ97JiN2hGnmjblwRAPmDvSldcSTjqkPfwRlDJ4ACm6_oSyemrGNgNN_68YljGdbPWA2Gh_oq9r_EhxsWoJkM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GySsJmVO32CiOhOL_TId7k1hobPOsm035nRzsMzF_CEaNreRZpQtRi8nKR00ny79t5MI2bZvaMRrU5mqEFM1wh_pu-fQsyAya-YyTk4fOyPIB4b6pNq7wtoP01bPCs1FR1KxHiftzjkUk6M8ta-zVNrJlAhCtMyEQ1Sy0PvyVga2oNotZRmr_1TV1dnB635eTmuu2shQS9bvO-LV0ervW81KGwZjT2ppgqjsJpi8V3tHuRy9nnbwhIEoAo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1087664,-11.8492807,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(68,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzcCIzEJs9zPSfnpLCCsxe_AhH8jLcQ7RzLjUo3C_s9qnqvAtNSc973Hd6K2EVyqJ_PTmSYvy3fpCLFTX-XqyOqF0_NRIbUYeZjHn1gAPMXT7BES6_gDxlAJO-hxpxr3dcD_y5oRim1iW2JNAdYxAX9RzrnmXsgAKGnx3sQcbWUpCsx801fHXYHIPQEyrr85QWPuXYGhYTt7wjkI_Gf_hWn7uSsnvgueUF0y4Y4nbKJa4-vcLqm3CSR1DY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA - CABLE ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwnqACqik_sjYpbTDgNuv1EmNGFXzpwR-ct6Udz6UwQUVjc_Fu0inYiuq0LR774NN44QR90rjrVS9czyorP-u-LTaJOQpCpOHfHUnPGbmkGmiZwxf5jrSSELgLTqSZMSPgVOZ4G1IGmGyxNN4WTQeD2NQ9sREEcPcUhWvSbH1_LSXauzrUWxNUtCBKpdYEn7rjJWNs1nyANN_A5om3TD_QOb3g12kLHxNWan9utRxzmOQrDwKg9Fw0kYkU?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywDDG8oprKaantfesm-XqoVOjQRcVd-_gWwZqJpnMQO74uOSUvjnajtZs1cu9y4KKWYVREWlAE7SRPwkcEghJsrtxmfwpzNb3RncWPrt_g-lOjj3JtG7e5y12-03sYl_0lkjr8RN4xt3DOk8gmC_-4lpPtO7AzCx1L1cK4xmjEnRkkxw5vbkhcx8LBvI6hUN_2WYLvBROJ5xz7yypHVEdp7Q3hXzwDMpjylO7yh43IbJXAAJahNzkta90?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1084441,-11.8490856,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(69,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAl_37pvf4_hp9jSJ_PYeq_ge8KWQz3DNC_KyyvJVvKq8z9n2nxauhm6sdyg48jTcdqf0yDAd2It5BKsGLdgv43mecQ9E89PlCOyp-0Zieof_UCBmppjuOihBc_D8fe1-Q7d8M9OSVTlq2rtrd3YDZU4rheaqNmP-CkKrzsA1PbD9L2NivvI1jvcuYAAZ0DLylwZGcO5v5PTExJyvjDK1G1l9quEeBdpolmKC5dqv0_5Y6QesSWxhU6vY?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO AZUL - TERMINA EN PUNTA <br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GywsJ0GMED71uL-UypU2Ko7E9iMXvyw9rLy3KV3ocd9w2WfZ_VMSKluDefyEv_ng5JoAyXSGII4lwHouaOIn6OU7yqav8oHX2tgydY1khX1wiiKCvBlfdIhwERhnEGBYyU8TjjdmaTx6eovmEZDbo-cSCFgQJOXCNfEUkTUm5cmNm6DW1J4PYgwB3x6m2UF4dNjdc5xiuPIfyythTqUcL72liBtQaKKX6H9uV0ZQ4SQT-uKIuxdupVCny0?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1080508,-11.8489455,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(70,3,'Point','MUFA PEQUEÑA','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwqwx-GtnzHdDOPa6HgtgBo1rfEaigxj-sFnz4xxWfgO2KUa6DHuJ6irMkndYk_pX2nIXgTU-o8i2GvMpqEjKkFzBPrxkZhT5NWTinjWAmTb9_VS93aJedoduQd8zhJgBx5ts-gCDYyF_uISsltcPv_tUMZKDYEYqojXQtu-NIwA_6rhl9XLeZQBnaIcC0c_fuN6-Q4YbCtVPRG8DAerPuCeBga1ym5BS1HItu0pFe_d8H-7rh0GQP6CVE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz_mHzIFYaoSsrW9DfhKJc44NJFuA1xrpE1nr3sE56TKAnK22vXbR3r2heNNez8Lu2ZePNss17rZJyE7wCiEf4eUWBJKHeU5SMQVmpF-r9XRir_IXMKITa4ctnpwkvHvLa_yxeOsCsVq_vavrIhGRiP8ctFVVK70jW9b5FjTlrrShsZDMMokeYP3K_TIF-DZucLDMSGJYu1H4g7WkxYdVTaXxfhBgghBeUOVbqXcVCUXcve0BjvfrDCKl8?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyttNLZrVzgET0_Kzrq0_6koEr0vLgTxiY3uPGRJfV2pJpryRNMC-62rfng_j87s2unjcIE8q24uakF-v_AGMS9k0-IlPo1jkEtViTpXjvxdqTbVd_oqZ9YliXtmC2hEkGEczhSIQ8y10XWW6o8s5fvA2Co4vOo7XQgu_G6Z18gYdczLP0Re8QGeA-xR56zU_ZCtN_obdnemyL1oeUd-EkPluywbDS6AaGHMvyR_IYXN1-xs-2Hm_YtvAw?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1066682,-11.847964,0]}','#fb923c',NULL,'2026-06-03 02:13:32',0,'','',''),(71,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxMmjp5Wf8yHrPAnQ1NzpGq6J9pFUPmz6DuBYJxyVVXdX5wPZ0LqUMZ8Alpzq1K4Zy9ZXMRdAIV5r19DHWjmQC4MGpGAUmCtdzoxyKO0oLmLgSm-Ro9KdwCxKpp86EkkoVheEUyRCqPxCfarqga10_tSpscMM9MHwN4vdsAyEQP-QxFbRJAakqrsdpqrN9Lkmtp8lUrDwsZ8a5inZkhGZ30QDhbw-9I3L1S_iB69nvA6kf_zK3w6WxRX1Y?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO MARRON - ASU 12 HILOS - EN ESTA CAJA ENCONTRAMOS UN SANGRADO Y 2 PUNTAS ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gz5r9VlmLri6xPKvzCuCRc1X_Djp0VFmOojvRpgJdbIO16-QRZcGKDIINkIKU3TseUgjNKvbLr7GRqbDc5N4DSRB_-RUd7njsnMpOYOoB-HBj5MCFRDEeu9ZgohiG0WMgnJDJWANOE6ZYSlTOjzAogyEgUE7S14dCcJIif4IwSNipz-UDA7qQfQdtp0maceXnr_vmH6f5GDZVMxvBrkLs2T6jxCkHSnohxD1TuuZERH99UJpGjePMY0470?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gw3eZB35BFpLfZ7fkk-chagzEwMmaHG73dr-uFdvDeBw2DwCCBeJRHdo0BriN-6KTtmAq-dkVt60RIht6-ixhApIKqHAdFdSwy6ZXHgORnqagp2XfE6ply0CT65TgA704bzDPYUHVfei3lRFnHAB2ziNxM3Yn_rR_LDPNiSMYCysM1ln2Z5uOUul6wnro66Wf7AtqVhF11jm_sAA4kFBJ0-UnPNahsZbGTWzoq2KCctAW3I0dKP7iQLIfQ?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwAa4c6R0OBVXg4Vp6Skdvq5WyGwrFnHtQIk4MZ_6UHUmtFLqmgpzKZ0WZQvkvnnB3yi2aYZmdvjVjzTZSGd-VHtRTuFeEY7wmdFORwZWz1L7csgqranwSgxmRxAWcLpfZ0sBMxWYJgu4XDq4ZqQmIBfKyokyH39qwfkZN_VCHgK1DWgl62rEWcSyRoWskCOAzyZpCv4eVyVC1rUELB885rmnzWC7BvxSUbMkRWMk7Xc-UN1pTI5AYjplk?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwVS6O4aF8Q35BmCAJIwh7EAKhf_NNIs_BGgWOnn0ONzAE3ijHUVXaTOn5WkXPTvmF4mF9DcM_tLCnngSc-a-jmoKYJEkPf2Wv6wB07fOcLq9-ojz39XyIF09tx7ieRuFUPoDelm21YdsbSCjL3VY3ix9dLC2woZnrDRChRsYOzhJvk3ZXJMDrIcAWgRhbeIC1EkV9u9U2x9X269hvJK9Vfa5MYMVxo8vQDXL-KLfHHiwZna5et0ZOpK4k?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073897,-11.8483011,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(72,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzo6dR8m17cW-nazjLAmPbhHLQnoojpPvz6h940l7A9rQmLGs9t1coiWt3DlRLhGGQPBYBx96wUaVJ5E7mLdKgkCKlc-xI1xzJ1yM8z4sWIqwKYOCbj5_rjI-4E4Zb3G_xbx6_WHJ-SDBBIZnV6Ci7-V0KQtUCyIANekbL9-nB2c82wSJOyJSoPaaf5rGX0fADaDM5jo1Bq4m7Yqs9EIBjEYR14MhcOLY6FzDWpJsxdVaPMsx4i-iXq6dA?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO VERDE - SANGRADO - ASU 12 HILOS - POTENCIA<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GwYEG_M55daI5Bt85sNSAYAwiRfz3XuOYtv5-gF0Y__UtLr4mZWKWBdffltuiRkpQZv0bVzuWm7oAgDzxa75psPCTeqlB_wFSFH3u3rSww-qGVWAuWJSxS0TkJZPrA26mKFiFbnqf0cmU_hfoknqhVThQjlF1TAJzQnIiaZkD3BflUDu8_Fi1yGw4Zn40OHrBQn4O49tShDwTzJxshPtNukngw30tE1K5DgVQirFB2XlntZWOhHTWfBrVM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gzf9B6IjbaSJo-IQKILIFHNrJCB3-sDLPQcHxmosU0QE_3WSnTfnoDpkjekcFHZlclF_nh_sKSGYeSYiVQTfPBovDZzLxh-sQ3wHzG4cxWBohNSMTlTvPBKvV7z2YAGDH9mU8sV_-d_jaJpRZRsVoR3DGSk1Q9akvwmZL3eQTypod5kGWMvGseDN95n4VLittlG390fasPWiiCBD3ILpOCPml8cgJa665nMekzmFg6Ljl1Z79_TRuvmIkg?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxP-PJMmYreI_XH3wRP9tSr1C8ZB8W0Vu5-dy4gW-Xwf_9D47QRprGHiLGSKbpu6Ktkm47bfcA-3NXXGheFg6RJSftSICszG81cmxztvG4i5ryJPGN9miXIXgUYcqOnjphhHIHM8-aBQZ7QeKTKSXc9rBB1LboeDTn5ET5y-yC-ldbIv-C-D62Lrl9YRtXbxKiFq7tqrhX7fYShh-CBpDcKiNQn1zEifxYitoN-4aw137gWmdCdIAsk624?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1072492,-11.8486635,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(73,3,'Point','CAJA NAP','<img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_Gwq0abaMggn5p7A3aGZxexuo3w7845mn4WxKfS8vXS6WOOsTjvO8Jqd3j3-1lfzzXQtE4WQ0lv8iu3NM25TO3__3TU18kGHsXYmceeqEnYdJLPjM5_kV3TYgXEoiJf5qQ6DDwWcPZnMm3I4_l2RqX6vtHOES4vcDdE_cKA6Ad6EQ-xSEfPaeLoIikZlHFC8Oq2QliZmv0SgongzrPX7gbkk-UemxQ4rXKsExx8V1AS2D6j7_KWebQhMclo?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br>HILO NARANJA- SANGRADO - ASU 12 HILOS<br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GzuQO8ma1ea46zvtpeqCHZ6WfyKJhH5QDFWQJhiz8kFxXA0pWO1k_v0OF723s5rqhWO8yQb0UAqxtQEKRQOb5itZAw6h3RMBPyBRAXxHc_am7GDt2BaMiAfmYjzAOqakmLWaDzexwBrM2vpNkNaHjuph5_B2brhlbpP9GQ9Ns4Uq865tR60YWSeu_cxyuqfX-td7iqJKk_xg0_D-vBoXy7bIg_Q6dvSYad9DC6BF_ynrpPikro23-jH3EI?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GyqKLOig_zSbkdYEvm2CFBySjXo1tqLHjawi-nWye14aGqan7SB--Bg6CznD-9yr3nS4yY6KrDH_Ni7R-yzC9N5y96L3Ka0ZtQh_1d0We6scg3rvbfUgQW4i7PqR8jOGNsghPlJPBfv2AAmyFJzkFSobkBiAZUGUqZJsqB2ZiJbQoj108_kBfPadje58AtTtXfCB1_s7u_EaEAvO9nVwYbdIIkQtm1wE0iL48urNGdwanp_n0m0V0L70eE?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" /><br><br><img src=\"https://mymaps.usercontent.google.com/hostedimage/m/*/3AE5a_GxlCDRBg2h7a4r4oTjbWmwR2yQGEMf0QAXmNf59rO039gJHKHY1-8oHaC1OjdGXl_4yI7m3YM6ydYPvqb0_ZtT6uYfLnuaHr2zklf9V8ilTvDp4ELqFf7himPDfRK0gkkT24BEc-4lbdWZYZS_DeUxg3GjgphWGCj7-9GudaPH6gUbUOX3lOkymXFm46xDrg9auZf9ANwJm-pr-29gqIOU6TzJLEKFQn9F-50syNbML-IpUfMaIHcVDloM?authuser=0&fife=s16383\" height=\"200\" width=\"auto\" />','{\"type\":\"Point\",\"coordinates\":[-77.1073076,-11.8489193,0]}','#facc15',NULL,'2026-06-03 02:13:32',0,'','',''),(74,3,'Point','CAJA NAP 8','','{\"type\":\"Point\",\"coordinates\":[-77.106729,-11.8486018,0]}','#facc15','ph-map-pin','2026-06-03 02:13:32',16,'','','1x16'),(75,3,'Point','CAJA NAP 8','','{\"type\":\"Point\",\"coordinates\":[-77.1074543,-11.847864,0]}','#facc15','ph-map-pin','2026-06-03 02:13:32',0,'','',''),(76,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1074803,-11.8483447,0],[-77.1073153,-11.8486952,0],[-77.1072492,-11.8486635,0],[-77.1073153,-11.8486965,0],[-77.1072228,-11.8488855,0],[-77.1073117,-11.8489255,0],[-77.1067225,-11.8486663,0],[-77.106729,-11.8486018,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(77,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1073897,-11.8483011,0],[-77.1075816,-11.8479201,0],[-77.1074543,-11.847864,0],[-77.1075816,-11.8479187,0],[-77.1075743,-11.8478433,0],[-77.1076876,-11.8475947,0],[-77.1078046,-11.8475511,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(78,3,'Point','MUFA PRINCIPAL','','{\"type\":\"Point\",\"coordinates\":[-77.1082255,-11.8486869,0]}','#ef4444',NULL,'2026-06-03 02:13:32',0,'','',''),(79,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085937,-11.8488589,0],[-77.1085736,-11.8488484,0],[-77.1084441,-11.8490856,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(80,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.108182,-11.848666,0],[-77.1080505,-11.8489482,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(81,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1082255,-11.8486869,0],[-77.1085736,-11.8488484,0],[-77.1086242,-11.8488073,0],[-77.1092679,-11.8491092,0],[-77.109858,-11.8493717,0],[-77.1097796,-11.849518,0],[-77.1100082,-11.8490488,0],[-77.1100538,-11.8490632,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(82,3,'Point','Punto 22','','{\"type\":\"Point\",\"coordinates\":[-77.1016017,-11.8477106,0]}','#2dd4bf','ph-buildings','2026-06-03 02:13:32',0,'','',''),(83,3,'Point','Punto 23','','{\"type\":\"Point\",\"coordinates\":[-77.1010109,-11.8471751,0]}','#facc15','ph-house','2026-06-03 02:13:32',8,'','','1x8'),(84,3,'Point','Punto 24','','{\"type\":\"Point\",\"coordinates\":[-77.1001958,-11.8466474,0]}','#a3e635','ph-tree','2026-06-03 02:13:32',0,'','',''),(85,3,'Point','Punto 25','','{\"type\":\"Point\",\"coordinates\":[-77.0994287,-11.8460857,0]}','#facc15','ph-camera','2026-06-03 02:13:32',0,'','',''),(86,3,'Polygon','zona','','{\"type\":\"Polygon\",\"coordinates\":[[[-77.1109445,-11.850463,0],[-77.1088416,-11.8506992,0],[-77.1084822,-11.849413,0],[-77.1109445,-11.850463,0]]]}','#a78bfa',NULL,'2026-06-03 02:13:32',0,'','',''),(87,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1104289,-11.8497406,0],[-77.109838,-11.8494319,0],[-77.1097904,-11.849518,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(88,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1091457,-11.8486418,0],[-77.1094349,-11.848776,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(89,3,'LineString','ASU 12 HILOS','','{\"type\":\"LineString\",\"coordinates\":[[-77.1087664,-11.8492807,0],[-77.1089288,-11.8490223,0],[-77.1092359,-11.8491772,0],[-77.1092775,-11.8490866,0]]}','#38bdf8',NULL,'2026-06-03 02:13:32',0,'','',''),(90,3,'LineString','Cable Principal','','{\"coordinates\":[[-77.10359679780905,-11.846906011049825],[-77.10303890685651,-11.84653687884871],[-77.1024260125712,-11.846044701805681]],\"type\":\"LineString\"}','#fbbf24','ph-map-pin','2026-06-03 02:14:54',0,'','',''),(92,3,'LineString','Cable Principal','','{\"coordinates\":[[-77.1055455800488,-11.85012393802431],[-77.10418041195774,-11.84913809545229],[-77.10392858483387,-11.849449414543855],[-77.10338516840905,-11.849027836522282]],\"type\":\"LineString\"}','#a78bfa','ph-map-pin','2026-06-05 01:31:27',0,'','',''),(93,3,'Point','0001','','{\"coordinates\":[-77.10597031889381,-11.844379919826522],\"type\":\"Point\"}','#a78bfa','uploads/mapas/icons/icon_6a222cd1c5003.png','2026-06-05 01:50:27',16,'','',''),(94,3,'Point','Nueva Caja','','{\"coordinates\":[-77.1046250325546,-11.847478946353107],\"type\":\"Point\"}','#a78bfa','ph-map-pin','2026-06-05 03:40:28',0,'','',''),(95,3,'Polygon','Nuevo Cable','','{\"coordinates\":[[[-77.10786957028446,-11.847126265803823],[-77.10826976054207,-11.848002849642924],[-77.10823799941049,-11.848015283436396],[-77.10776069472367,-11.846995742361699],[-77.1079980645578,-11.847309191066941],[-77.10786957028446,-11.847126265803823]]],\"type\":\"Polygon\"}','#a78bfa','ph-map-pin','2026-06-05 03:53:49',0,'','',''),(96,3,'Polygon','Nuevo Cable','','{\"coordinates\":[[[-77.10119018839906,-11.849802300194327],[-77.10322912356095,-11.850727540324755],[-77.10184631632328,-11.851908252655136],[-77.10119018839906,-11.849802300194327]]],\"type\":\"Polygon\"}','#e879f9','ph-map-pin','2026-06-05 03:54:05',0,'','',''),(97,3,'Polygon','Nueva Zona','','{\"coordinates\":[[[-77.11219516282539,-11.846632881985258],[-77.11425729328307,-11.84839682218643],[-77.11235753530246,-11.848794104442817],[-77.1121626883302,-11.846648773388623],[-77.11219516282539,-11.846632881985258]]],\"type\":\"Polygon\"}','#a78bfa','ph-map-pin','2026-06-05 04:25:13',0,'','','');
/*!40000 ALTER TABLE `mapas_elementos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas_imagenes`
--

DROP TABLE IF EXISTS `mapas_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_imagenes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elemento_id` int(11) NOT NULL,
  `ruta` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `elemento_id` (`elemento_id`),
  CONSTRAINT `mapas_imagenes_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `mapas_elementos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas_imagenes`
--

LOCK TABLES `mapas_imagenes` WRITE;
/*!40000 ALTER TABLE `mapas_imagenes` DISABLE KEYS */;
INSERT INTO `mapas_imagenes` VALUES (1,14,'uploads/mapas/map_6a1f88e36960e.jpeg','2026-06-03 01:52:35'),(2,14,'uploads/mapas/map_6a1f88ebd8da6.jpeg','2026-06-03 01:52:43'),(3,14,'uploads/mapas/map_6a1f88f71e8a4.png','2026-06-03 01:52:55'),(4,31,'uploads/mapas/map_6a1f8d543557e.jpeg','2026-06-03 02:11:32'),(5,31,'uploads/mapas/map_6a1f8d579d3ce.jpeg','2026-06-03 02:11:35'),(6,65,'uploads/mapas/map_6a1f8de40da62.jpeg','2026-06-03 02:13:56'),(7,65,'uploads/mapas/map_6a1f8de6dd253.jpeg','2026-06-03 02:13:58'),(8,65,'uploads/mapas/map_6a1f8deadb71e.jpeg','2026-06-03 02:14:02'),(10,93,'uploads/mapas/map_6a222b745fb66.jpg','2026-06-05 01:50:44'),(11,93,'uploads/mapas/map_6a222b7bd9d16.jpg','2026-06-05 01:50:51'),(12,93,'uploads/mapas/map_6a222b821b76e.jpg','2026-06-05 01:50:58'),(13,93,'uploads/mapas/map_6a222b8630ca8.jpg','2026-06-05 01:51:02'),(14,93,'uploads/mapas/map_6a222b8c4f723.jpg','2026-06-05 01:51:08');
/*!40000 ALTER TABLE `mapas_imagenes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas_proyectos`
--

DROP TABLE IF EXISTS `mapas_proyectos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_proyectos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas_proyectos`
--

LOCK TABLES `mapas_proyectos` WRITE;
/*!40000 ALTER TABLE `mapas_proyectos` DISABLE KEYS */;
INSERT INTO `mapas_proyectos` VALUES (1,'Proyecto Zapallal','',NULL,'2026-06-03 01:41:55','2026-06-03 01:41:55'),(2,'Mapa de prueba 002','',NULL,'2026-06-03 02:12:37','2026-06-03 02:12:37'),(3,'000102','',NULL,'2026-06-03 02:13:29','2026-06-03 02:13:29');
/*!40000 ALTER TABLE `mapas_proyectos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas_puertos`
--

DROP TABLE IF EXISTS `mapas_puertos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_puertos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `elemento_id` int(11) NOT NULL,
  `numero_puerto` int(11) NOT NULL,
  `estado` varchar(20) DEFAULT 'Disponible',
  `cliente_nombre` varchar(255) DEFAULT '',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_puerto` (`elemento_id`,`numero_puerto`),
  CONSTRAINT `mapas_puertos_ibfk_1` FOREIGN KEY (`elemento_id`) REFERENCES `mapas_elementos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas_puertos`
--

LOCK TABLES `mapas_puertos` WRITE;
/*!40000 ALTER TABLE `mapas_puertos` DISABLE KEYS */;
INSERT INTO `mapas_puertos` VALUES (1,83,1,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(2,83,2,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(3,83,3,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(4,83,4,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(5,83,5,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(6,83,6,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(7,83,7,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(8,83,8,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(9,83,9,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(10,83,10,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(11,83,11,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(12,83,12,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(13,83,13,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(14,83,14,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(15,83,15,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(16,83,16,'Disponible','','2026-06-05 03:07:30','2026-06-05 03:07:30'),(17,74,1,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(18,74,2,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(19,74,3,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(20,74,4,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(21,74,5,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(22,74,6,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(23,74,7,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(24,74,8,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(25,74,9,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(26,74,10,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(27,74,11,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(28,74,12,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(29,74,13,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(30,74,14,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(31,74,15,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(32,74,16,'Disponible','','2026-06-05 03:08:19','2026-06-05 03:08:19'),(33,93,1,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(34,93,2,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(35,93,3,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(36,93,4,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(37,93,5,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(38,93,6,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(39,93,7,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(40,93,8,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(41,93,9,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(42,93,10,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(43,93,11,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(44,93,12,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(45,93,13,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(46,93,14,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(47,93,15,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03'),(48,93,16,'Disponible','','2026-06-05 03:27:03','2026-06-05 03:27:03');
/*!40000 ALTER TABLE `mapas_puertos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas_puertos_historial`
--

DROP TABLE IF EXISTS `mapas_puertos_historial`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas_puertos_historial` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `puerto_id` int(11) NOT NULL,
  `accion` varchar(50) DEFAULT NULL,
  `cliente_nombre` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `puerto_id` (`puerto_id`),
  CONSTRAINT `mapas_puertos_historial_ibfk_1` FOREIGN KEY (`puerto_id`) REFERENCES `mapas_puertos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas_puertos_historial`
--

LOCK TABLES `mapas_puertos_historial` WRITE;
/*!40000 ALTER TABLE `mapas_puertos_historial` DISABLE KEYS */;
INSERT INTO `mapas_puertos_historial` VALUES (1,1,'Cambio a Disponible','','2026-06-05 03:07:36');
/*!40000 ALTER TABLE `mapas_puertos_historial` ENABLE KEYS */;
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
-- Table structure for table `servicios`
--

DROP TABLE IF EXISTS `servicios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `servicios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `velocidad` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `servicios`
--

LOCK TABLES `servicios` WRITE;
/*!40000 ALTER TABLE `servicios` DISABLE KEYS */;
INSERT INTO `servicios` VALUES (1,'Paquete','','700 Mbps','2026-05-26 02:05:22');
/*!40000 ALTER TABLE `servicios` ENABLE KEYS */;
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
INSERT INTO `settings` VALUES ('app_name','Turbo Perú'),('bg_color','#ffffff'),('contact_email','contacto@turbosaas.com'),('currency','PEN'),('date_format','Y-m-d'),('favicon','uploads/settings/favicon_1778558219.png'),('global_notification_banner','fsdfsdfsdfsfsfd'),('global_notification_push','0'),('hover_effect','shadow'),('logo_collapsed_dark','uploads/settings/logo_collapsed_dark_1779752809.webp'),('logo_collapsed_light','uploads/settings/logo_collapsed_light_1779752809.webp'),('logo_dark','uploads/settings/logo_dark_1778600385.png'),('logo_light','uploads/settings/logo_light_1778534066.png'),('logo_pwa','uploads/settings/logo_pwa_1778558219.png'),('maintenance_mode','0'),('phone_main',''),('phone_secondary',''),('primary_color_dark','#f07d00'),('primary_color_light','#0e4194'),('ruc',''),('slogan',''),('social_links','{}'),('text_color','#333333'),('toast_position','bottom-right'),('toast_style','card'),('typography','Outfit'),('website',''),('work_hours','');
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
  `barcode` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `pin` (`pin`),
  UNIQUE KEY `barcode` (`barcode`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin','admin@admin.com','admin@turbosaas.com','$2y$10$taTnrJmWuABFcs/uDblnXOM1dX9X7ucPs/Pkdo/46C82Up0eDiGVC','admin','2026-05-11 20:00:24','4444','','uploads/profiles/prof_1_6a0fd01c9f281.png','uploads/profiles/cov_1_6a0fccdb0eb61.jpeg',NULL),(3,'Cesar Alexander Mendoza Castro',NULL,'cesarestudio2395@gmail.com','$2y$10$S0uTJp/G.IGhvZ.i3pNUSezj2/liQKfTw5RSwH4nW9hRCwcQfcSji','admin','2026-05-11 21:12:04','2308',NULL,NULL,NULL,NULL),(4,'Ronald',NULL,'ronald123@gmail.com','$2y$10$a0S5LbpaJY6UIm6I6VrkJu4rBxOfKf5eQUoYEobk8PPzsQHjd9izO','Tecnico','2026-05-12 16:45:31','1234','','uploads/profiles/user_4_1778615410.jpg',NULL,NULL),(5,'Javier Mendoza',NULL,'74589633566@cliente.turbosaas.com','$2y$10$jL7X9Ng43gjGHJ6QVRlY/uNuGTolxHlt/ZvowJyW3Fd1x.qtmUKWO','Cliente','2026-05-13 00:57:01','123456','95621450000',NULL,NULL,NULL),(6,'MENDOZA CASTRO CESAR ALEXANDER',NULL,'10742146362@cliente.turbosaas.com','$2y$10$k1IMxdQtk0s7.pYvvw0kl.QMFnkWkKO3yEX2ZzYt1nixx9gip9jlK','Cliente','2026-05-13 01:25:52','082420','95621450000',NULL,NULL,NULL),(8,'Test Cronometro',NULL,'12345678@cliente.turbosaas.com','$2y$10$m05/ISIeAdU9VZNqEZhbzu4Vu41KoLuAS79BXv5QC.WL9.4xJ8Pb6','Cliente','2026-05-16 17:29:57','254395','',NULL,NULL,NULL),(9,'Luis Lopez',NULL,'121565656565656565656565656123@cliente.turbosaas.com','$2y$10$fVXdsr./C4WLSH3DBb10c.D5fYx9gmpouWqtg53OT3VxRYigeutzS','Cliente','2026-05-16 18:01:00','537710','',NULL,NULL,NULL),(11,'Luis Lopez',NULL,'c_1778995826_121565656565656565656565656123@cliente.turbosaas.com','$2y$10$JHeD7PeNwJwkatFaRwpVv.BaHCTsORn8G.6B3ILMa83yTksI6.g8e','Cliente','2026-05-17 05:30:26','890772','',NULL,NULL,NULL),(13,'Luis Lopez',NULL,'c_1778995841_121565656565656565656565656123@cliente.turbosaas.com','$2y$10$Db.bAczDOucxgshkO0jNm.dzuuMwf3sEfiIPUFJHqS0U12iaeens.','Cliente','2026-05-17 05:30:41','220288','',NULL,NULL,NULL),(14,'Miguel Sanchez',NULL,'miguelsa@gmail.coim','$2y$10$2p3w093MgDBFA57yopRKS.LQXbJvZ7vKPlIz7AGBdd7SN3CNbzpoO','Tecnico','2026-05-25 22:50:42','8428',NULL,NULL,NULL,'USR-83E387'),(15,'Pedro Lopez',NULL,'pedrolopez@gmail.com','$2y$10$IVGW5BDJ.Q5HXHM099GcVOqCHh5IXJg.SGsi/wgchX/kYGV2d6oMy','Tecnico','2026-05-25 22:58:13','6274',NULL,NULL,NULL,'USR-866824');
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

-- Dump completed on 2026-06-18 23:58:44
