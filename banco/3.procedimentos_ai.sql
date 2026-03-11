-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `tecnologia`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `procedimentos_ai`
--

CREATE TABLE `procedimentos_ai` (
  `id` int(11) NOT NULL,
  `procedimento_id` int(11) NOT NULL,
  `resumo` text DEFAULT NULL,
  `palavras_chave` varchar(255) DEFAULT NULL,
  `embedding` longtext DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Extraindo dados da tabela `procedimentos_ai`
--

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `procedimentos_ai`
--
ALTER TABLE `procedimentos_ai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ai_procedimento` (`procedimento_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `procedimentos_ai`
--
ALTER TABLE `procedimentos_ai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `procedimentos_ai`
--
ALTER TABLE `procedimentos_ai`
  ADD CONSTRAINT `fk_ai_procedimento` FOREIGN KEY (`procedimento_id`) REFERENCES `procedimentos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
