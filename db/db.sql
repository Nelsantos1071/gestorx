-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Tempo de geração: 12/08/2025 às 02:38
-- Versão do servidor: 5.7.42
-- Versão do PHP: 8.1.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `appsxtop_gestorx`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `senha` varchar(255) DEFAULT NULL,
  `nivel` enum('admin','superadmin') DEFAULT 'admin',
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `admins`
--

INSERT INTO `admins` (`id`, `nome`, `email`, `senha`, `nivel`, `criado_em`) VALUES
(1, 'Nelsantos', 'nelsantos.soft@gmail.com', '$2y$10$quDc4/XTBAEsFD2rIy6jguk9Tu1TcvCJWK.8A4ionFYszG2KRZ8im', 'superadmin', '2025-05-22 10:44:17');

-- --------------------------------------------------------

--
-- Estrutura para tabela `alugueis`
--

CREATE TABLE `alugueis` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `quantidade` int(11) NOT NULL DEFAULT '1',
  `data_aluguel` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data_cancelamento` datetime DEFAULT NULL,
  `status` enum('ativo','inativo') DEFAULT 'ativo',
  `url_download` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `usuario` varchar(100) DEFAULT NULL,
  `senha` varchar(100) DEFAULT NULL,
  `chave_pix` varchar(255) DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `servico_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `alugueis`
--

INSERT INTO `alugueis` (`id`, `cliente_id`, `produto_id`, `quantidade`, `data_aluguel`, `data_cancelamento`, `status`, `url_download`, `site`, `usuario`, `senha`, `chave_pix`, `data_fim`, `servico_id`) VALUES
(1, 3, NULL, 1, '2025-06-06 13:52:20', NULL, 'ativo', 'http://aftv.news/8216291', 'https://streamy.appsx.top', 'Gabrieltv', '$2y$10$TcMMUCcz0gfDvDLXkB.YxeT5LSHKCxAs1CxzDv2Hahtg3BeQDNQN.', '309.675.269-09', '2025-07-06', 2),
(2, 2, NULL, 1, '2025-06-06 13:53:45', NULL, 'ativo', 'http://aftv.news/8498754', 'https://alfa.appsx.top', 'admin', '$2y$10$S3UUj8auVNF4fnBirkMVGOOK5LIWEHFwUYkIamX6J7n9Mp7cIkFOa', '309.675.268-09', '2025-07-06', 2);

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho_temp`
--

CREATE TABLE `carrinho_temp` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `criado_em` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `token` varchar(64) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT '1',
  `cadastrado_por` int(11) DEFAULT NULL,
  `criado_em` datetime NOT NULL,
  `liberar_funcoes` tinyint(1) NOT NULL DEFAULT '0',
  `vencimento` date DEFAULT NULL,
  `plano_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `telefone` varchar(15) DEFAULT NULL,
  `chave_pix` varchar(255) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `clientes`
--

INSERT INTO `clientes` (`id`, `nome`, `email`, `senha`, `token`, `ativo`, `cadastrado_por`, `criado_em`, `liberar_funcoes`, `vencimento`, `plano_id`, `user_id`, `telefone`, `chave_pix`, `token_expiracao`) VALUES
(1, 'Manoel', 'nelsantos1071@gmail.com', '$2y$10$97yvAZUtMmBykHTlKJUiGebyhsOi/hkZBzKgG6rZWRWWrsaTYs//m', NULL, 1, NULL, '2025-06-05 11:45:36', 0, NULL, NULL, NULL, '11933742636', '309.675.268-09', NULL),
(2, 'ALFA STREAMING', 'lucasvivolopes12@gmail.com', '$2y$10$QMgtnTYsctU7JHUJZhtwAOPzWxZZRWcnrUbHOB/LydOKe1BQdT0/G', '918ef2c2be013c52d1ff08f6d081d3e5', 1, NULL, '2025-06-03 22:45:05', 0, NULL, NULL, NULL, '44998145102', '44998145102', NULL),
(3, 'Streamy', 'streamypix@gmail.com', '$2y$10$GZKDKjbrHBlN/Eg4GFMtD.BsJHDL60b89tvwXitHWlVpkZtznueYG', 'eaf10f13af2ead4eac3b7d025e3a5eb8', 1, NULL, '2025-06-03 22:51:00', 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `compras`
--

CREATE TABLE `compras` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT '1',
  `data_compra` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `valor_total` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `faturas`
--

CREATE TABLE `faturas` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plano_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('pendente','pago','cancelado') DEFAULT 'pendente',
  `data_emissao` date NOT NULL,
  `data_vencimento` date NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `pix_string` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `user_id` int(11) NOT NULL,
  `email_usuario` varchar(255) NOT NULL,
  `valor_1` decimal(10,2) NOT NULL,
  `valor_2` decimal(10,2) NOT NULL,
  `valor_3` decimal(10,2) NOT NULL,
  `valor_4` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cliente_id` int(11) DEFAULT NULL,
  `duracao` int(11) NOT NULL DEFAULT '30'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `nome`, `user_id`, `email_usuario`, `valor_1`, `valor_2`, `valor_3`, `valor_4`, `criado_em`, `cliente_id`, `duracao`) VALUES
(1, 'Mensal (25,00)', 0, '', 25.00, 50.00, 70.00, 150.00, '2025-05-30 00:20:59', 1, 30),
(2, 'Trimestal (R$ 50,00)', 0, '', 50.00, 50.00, 70.00, 120.00, '2025-06-03 01:10:42', 1, 30),
(3, 'Semestral (R$ 70,00)', 0, '', 70.00, 70.00, 100.00, 150.00, '2025-06-03 01:13:55', 1, 30),
(4, 'Trimestal (R$ 50,00)', 0, '', 50.00, 70.00, 100.00, 150.00, '2025-06-03 01:14:20', 1, 30),
(5, 'Mensal (R$ 30,00)', 0, '', 30.00, 0.00, 0.00, 0.00, '2025-06-03 02:11:30', 1, 30),
(6, 'Trimestal (R$ 70,00)', 0, '', 70.00, 0.00, 0.00, 0.00, '2025-06-03 02:12:12', 1, 30),
(7, 'Semestal (R$ 100,00)', 0, '', 100.00, 0.00, 0.00, 0.00, '2025-06-03 02:12:26', 1, 30),
(8, 'Anual (R$ 150,00)', 0, '', 150.00, 0.00, 0.00, 0.00, '2025-06-03 02:12:40', 1, 30),
(9, 'Mensal', 0, '', 25.00, 0.00, 0.00, 0.00, '2025-06-03 22:57:05', 2, 30),
(10, 'Trimestral', 0, '', 60.00, 0.00, 0.00, 0.00, '2025-06-03 22:57:28', 2, 30),
(11, 'Semestral', 0, '', 109.90, 0.00, 0.00, 0.00, '2025-06-03 22:57:43', 2, 30),
(12, 'Mensal (R$ 30,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:26:30', 4, 30),
(13, 'Trimestal (R$ 70,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:26:41', 4, 30),
(14, 'Semestral (R$ 100,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:27:35', 4, 30),
(15, 'Anual (R$ 150,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:28:25', 4, 30),
(16, 'Mensal (R$ 25,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:28:38', 4, 30),
(17, 'Trimestal (R$ 50,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:29:01', 4, 30),
(18, 'Semestral (R$ 70,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:29:23', 4, 30),
(19, 'Anual (R$ 120,00)', 0, '', 0.00, 0.00, 0.00, 0.00, '2025-06-05 10:29:35', 4, 30);

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text NOT NULL,
  `preco` decimal(10,2) NOT NULL DEFAULT '0.00',
  `url_img` varchar(255) DEFAULT NULL,
  `saiba_mais` text,
  `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Despejando dados para a tabela `produtos`
--

INSERT INTO `produtos` (`id`, `titulo`, `descricao`, `preco`, `url_img`, `saiba_mais`, `criado_em`) VALUES
(9, 'SMARTERS V3', 'O SMARTERS V3 é um software voltado à donos e revendedores de serviços de iptv bem como canais, filmes e séries', 150.00, '683e3f4a3d33c.jpg', '<p>O Painel é instalado em um servidor VPS ou hospedagem CPANEL:</p>\r\n<ul>\r\n    <li><strong>Smarters V3:</strong> O mesmo é utilizado para realizar algumas modificações,</li>\r\n    <li><strong>DNS:</strong> principalmente a troca da DNS remotamente, o Apk busca as informações do painel através da API.</li>\r\n</ul>\r\n<p>Para mais detalhes, Acesse nossa Demo.</p>\r\n<p><a href=\"https://apps.appsx.top/v3\">https://apps.appsx.top</a>.</p>\r\n<p>Login: admin</p>\r\n<p>Senha: admin</p>\r\n\r\nR$ 10,00 Mensal', '2025-06-03 00:18:18'),
(10, 'IBO PRO PLAYER COM 5 TEMAS', 'É um software voltado à donos e revendedores de serviços de streaming bem como canais, filmes e séries', 150.00, '683eeb74224fc.jpg', '<p>O Painel é instalado em um servidor VPS ou hospedagem CPANEL:</p>\r\n<ul>\r\n    <li><strong>IBO PRO ADS:</strong> O mesmo é utilizado para realizar algumas modificações,</li>\r\n    <li><strong>DNS:</strong> principalmente a troca da DNS remotamente, o Apk busca as informações do painel através da API.</li>\r\n</ul>\r\n<p>Para mais detalhes, Acesse nossa Demo.</p>\r\n<p><a href=\"https://apps.appsx.top/ibo\">https://apps.appsx.top</a>.</p>\r\n<p>Login: admin</p>\r\n<p>Senha: admin</p>\r\n\r\nR$ 10,00 Mensal', '2025-06-03 12:32:52'),
(11, 'SMARTERS V4', 'É um software desenvolvido especialmente para provedores e revendedores de conteúdo via streaming, proporcionando maior controle e praticidade no uso e distribuição do serviço.', 150.00, '683eef1b55193.jpg', '<p>O Painel é instalado em um servidor VPS ou hospedagem CPANEL:</p>\r\n<ul>\r\n    <li><strong>SMARTERS V4:</strong> O mesmo é utilizado para realizar algumas modificações,</li>\r\n    <li><strong>DNS:</strong> principalmente a troca da DNS remotamente, o Apk busca as informações do painel através da API.</li>\r\n</ul>\r\n<p>Para mais detalhes, Acesse nossa Demo.</p>\r\n<p><a href=\"https://apps.appsx.top/goldPanelSmart4\">https://apps.appsx.top</a>.</p>\r\n<p>Login: admin</p>\r\n<p>Senha: admin</p>\r\n\r\nR$ 10,00 Mensal', '2025-06-03 12:48:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos_servicos`
--

CREATE TABLE `produtos_servicos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `descricao` text,
  `preco` decimal(10,2) NOT NULL,
  `tipo` enum('produto','servico') NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `produtos_servicos`
--

INSERT INTO `produtos_servicos` (`id`, `titulo`, `descricao`, `preco`, `tipo`, `imagem`, `criado_em`) VALUES
(1, 'Código para Dowloader', 'Código para busca as informações do app pra dowload, em firestick e tv android.', 10.00, 'servico', 'Uploads/servico/683e40f016c36.jpg', '2025-05-30 15:52:43'),
(2, 'Aluguel de Apps', 'Aluguel de Apps / Hospedagem', 10.00, 'servico', 'Uploads/produto/68410964e8bf4.png', '2025-06-05 03:05:08');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servidores`
--

CREATE TABLE `servidores` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `marca` varchar(255) NOT NULL,
  `plano_mensal` decimal(10,2) NOT NULL DEFAULT '0.00',
  `plano_trimestral` decimal(10,2) NOT NULL DEFAULT '0.00',
  `plano_semestral` decimal(10,2) NOT NULL DEFAULT '0.00',
  `plano_anual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `criado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ativo` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `servidores`
--

INSERT INTO `servidores` (`id`, `cliente_id`, `marca`, `plano_mensal`, `plano_trimestral`, `plano_semestral`, `plano_anual`, `criado_em`, `ativo`) VALUES
(6, 2, 'Alfa Streaming ', 0.00, 0.00, 0.00, 0.00, '2025-06-03 22:56:47', 1),
(10, 1, 'GenusTV', 0.00, 0.00, 0.00, 0.00, '2025-06-05 13:24:30', 1),
(11, 1, 'Blade', 0.00, 0.00, 0.00, 0.00, '2025-06-05 13:24:37', 1),
(12, 1, 'Power', 0.00, 0.00, 0.00, 0.00, '2025-06-05 13:24:42', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `template_users`
--

CREATE TABLE `template_users` (
  `id` int(11) NOT NULL,
  `cliente_id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `template_text` text NOT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `template_users`
--

INSERT INTO `template_users` (`id`, `cliente_id`, `titulo`, `template_text`, `updated_at`) VALUES
(1, 1, 'nova renovacao', 'ola${nome} fods${pix}', '2025-06-03 00:48:43'),
(2, 1, 'Vencimento', 'Olá ${nome}, tudo bem ?.\r\n Passando para lembrar que A data vencimento do seu plano é ${vencimento}, \r\nEvite o bloqueio automático do seu sinal\r\n\r\nPara renovar o seu plano agora, pode utilizar nossa chave pix abaixo:.\r\n? Chave Pix:.\r\n${pix}\r\n\r\nObservações: Deixar campo de descrição em branco ou se precisar coloque SUPORTE TÉCNICO\r\n\r\nPor favor, nos envie o comprovante de pagamento assim que possível.\r\n\r\nÉ sempre um prazer te atender.', '2025-06-03 00:48:47'),
(3, 1, 'wrtrtewrt', 'terterw${nome}rtewrtertwe${cliente_id}tewrtewrtewrt${pix}terter\r\n\r\ntrthgtr${pix}n${valor_4}', '2025-06-03 00:48:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `usuario` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `servidor_id` int(11) NOT NULL,
  `plano_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `vencimento` date DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `ativo` tinyint(4) NOT NULL DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`user_id`, `nome_completo`, `usuario`, `senha`, `celular`, `servidor_id`, `plano_id`, `created_at`, `vencimento`, `created_by`, `ativo`) VALUES
(3, 'Rodrigo Michele', '513429619', '683182597', '44988018617', 6, 11, '2025-06-03 23:15:57', '2026-01-03', 2, 1),
(4, 'Natan sao jorge do ivai', '911193217', '182217659', '44988223881', 6, 9, '2025-06-03 23:17:03', '2025-07-03', 2, 1),
(5, 'Vitor Duarte Atalaia', '606524725', '050696254', '44933003855', 6, 9, '2025-06-03 23:18:17', '2025-08-04', 2, 1),
(6, 'Mislene Atalaia PR', '509238485', '147634680', '11913430965', 6, 9, '2025-06-03 23:19:48', '2025-08-13', 2, 1),
(7, 'Alex charque', '873026020', '601572819', '44991066137', 6, 9, '2025-06-03 23:20:53', '2025-07-31', 2, 1),
(8, 'Dir pintura', '573914536', '250787076', '44991822813', 6, 9, '2025-06-03 23:21:50', '2025-08-04', 2, 1),
(9, 'Orelha Matinhos', '070125159', '186681333', '41987212563', 6, 10, '2025-06-03 23:22:53', '2025-09-28', 2, 1),
(10, 'Juliana Boticario', '319338415', '686005820', '44988094266', 6, 9, '2025-06-03 23:23:51', '2025-08-25', 2, 1),
(11, 'Leandro Londrina', '994929072', '732245096', '43999360401', 6, 11, '2025-06-03 23:24:49', '2025-08-25', 2, 1),
(12, 'Heliane', '872236510', '791453405', '44991585495', 6, 9, '2025-06-03 23:30:43', '2025-07-24', 2, 1),
(13, 'Flavia atalaia', '898579643', '934039910', '44991835449', 6, 9, '2025-06-03 23:31:58', '2025-08-04', 2, 1),
(14, 'Roseli Atalaia', '118620616', '593583430', '44997045268', 6, 9, '2025-06-03 23:33:28', '2025-08-19', 2, 1),
(15, 'Kaua Jogador', '755477610', '178423227', '44991186910', 6, 9, '2025-06-03 23:38:24', '2025-08-04', 2, 1),
(16, 'Jhessica Sao jorge do ivai', '737628836', '012452120', '44988470245', 6, 11, '2025-06-03 23:41:03', '2026-05-15', 2, 1),
(17, 'Marcia Sao Jorge do ivai', '794311956', '225864241', '44988162643', 6, 11, '2025-06-03 23:41:58', '2026-07-15', 2, 1),
(18, 'Fabiana Sao jorge do ivai', '611987841', '463228666', '44988112024', 6, 9, '2025-06-03 23:43:21', '2025-06-14', 2, 1),
(19, 'Paulo Alto PR', '728234797', '626524793', '44988387465', 6, 9, '2025-06-03 23:44:25', '2025-07-15', 2, 1),
(20, 'Rafaela Atalaia PR', '261337642', '317182826', '44998189186', 6, 10, '2025-06-03 23:45:24', '2025-08-13', 2, 1),
(21, 'Marli Atalaia PR', '467905020', '006793598', '44998075206', 6, 10, '2025-06-03 23:46:14', '2025-07-15', 2, 1),
(22, 'Amanda Sao jorge do ivai', '914029061', '740882812', '44988073490', 6, 9, '2025-06-03 23:48:18', '2025-07-08', 2, 1),
(23, 'Maria Sao jorge do ivai', '617393380', '463276965', '44988015515', 6, 10, '2025-06-03 23:49:31', '2025-08-06', 2, 1),
(24, 'Douglas Atalaia PR', '771667500', '323554490', '44988424306', 6, 11, '2025-06-03 23:50:46', '2025-12-04', 2, 1),
(25, 'Jor Atalaia PR', '035051868', '429490677', '44988550907', 6, 9, '2025-06-03 23:53:16', '2025-07-02', 2, 1),
(26, 'Pamela Sao jorge do Ivai', '316134348', '516876241', '44988173681', 6, 11, '2025-06-03 23:54:38', '2025-10-21', 2, 1),
(27, 'Gaucho Sao jorge do ivai', '303923617', '564258049', '44988426939', 6, 10, '2025-06-03 23:55:43', '2025-07-21', 2, 1),
(28, 'Marcos Robinho', '491287491', '422279339', '44999615192', 6, 11, '2025-06-03 23:57:49', '2025-11-21', 2, 1),
(29, 'Isaias Sao jorge do ivai', '181858958', '931282879', '44988429242', 6, 10, '2025-06-03 23:58:56', '2025-07-18', 2, 1),
(30, 'Murilo Paulo Boi', '477271480', '594921251', '11992566869', 6, 9, '2025-06-03 23:59:59', '2025-07-06', 2, 1),
(31, 'Angelica Sao jorge do ivai', '695025563', '209385418', '44988378521', 6, 11, '2025-06-04 00:01:04', '2025-07-06', 2, 1),
(32, 'Wesley Vanderlei Sao jorge do ivai', '450362233', '860436099', '14997059181', 6, 10, '2025-06-04 00:04:50', '2025-07-28', 2, 1),
(33, 'Andre Sao jorge do ivai', '079545038', '549033885', '44988372002', 6, 11, '2025-06-04 00:06:33', '2026-03-24', 2, 1),
(34, 'Celia Sao jorge do ivai', '650847112', '769716240', '44988336876', 6, 11, '2025-06-04 00:07:53', '2025-10-23', 2, 1),
(35, 'Lucas Tamires', '634892250', '603108030', '44988683047', 6, 9, '2025-06-04 00:09:35', '2025-06-19', 2, 1),
(36, 'Francisco sogro Cristiane', '839571482', '080410217', '44988344059', 6, 11, '2025-06-04 00:14:10', '2025-10-05', 2, 1),
(37, 'Allan Sao jorge do ivai', '669118167', '076359436', '44988071982', 6, 10, '2025-06-04 00:16:57', '2025-08-14', 2, 1),
(38, 'Joao rigolin', '576342070', '143314366', '44998012771', 6, 10, '2025-06-04 00:19:04', '2025-07-16', 2, 1),
(39, 'Joao Vitor', '063561911', '149774505', '44988287741', 6, 11, '2025-06-04 00:20:00', '2025-12-30', 2, 1),
(40, 'Toto Sao jorge do ivai', '922554251', '693744723', '41988623755', 6, 11, '2025-06-04 00:21:35', '2026-03-05', 2, 1),
(41, 'Pai Renan', '729618050', '244631445', '44988092331', 6, 10, '2025-06-04 00:26:28', '2026-07-03', 2, 1),
(42, 'Vera mae angelica Sao jorge do ivai', '573522217', '712745662', '44988226704', 6, 11, '2025-06-04 00:29:59', '2025-07-29', 2, 1),
(43, 'Ines Sao Jorge do ivai', '859007281', '923220187', '44988421469', 6, 11, '2025-06-04 00:31:38', '2025-10-18', 2, 1),
(44, 'Danilo Batore', '188354108', '105661314', '44988284472', 6, 11, '2025-06-04 00:32:52', '2025-09-11', 2, 1),
(45, 'Claudio Sao jorge do ivai', '763051131', '640586537', '44984029455', 6, 11, '2025-06-04 00:34:02', '2025-08-14', 2, 1),
(46, 'Vanessa Renan', '302189989', '895195534', '44988587661', 6, 9, '2025-06-04 00:35:22', '2025-08-07', 2, 1),
(47, 'Cristiano Robinho', '253418314', '783614703', '44999933882', 6, 11, '2025-06-04 00:37:31', '2026-07-27', 2, 1),
(48, 'Fabiano Sao jorge do ivai', 'Fabianotv', '933085959', '44988541958', 6, 11, '2025-06-04 00:38:38', '2025-08-19', 2, 1),
(49, 'Bruno Londrina', '677304628', '154803541', '43984315172', 6, 10, '2025-06-04 00:40:04', '2025-08-13', 2, 1),
(50, 'Araujo sao jorge do ivai', '717076163', '314962173', '44988684784', 6, 11, '2025-06-04 00:41:07', '2026-01-17', 2, 1),
(51, 'Andressa Wil', '504457', '949931', '44988275176', 6, 11, '2025-06-04 00:42:04', '2025-07-05', 2, 1),
(52, 'Andressa Fabiano', 'AndressaTV', '5167056376', '44988441067', 6, 11, '2025-06-04 00:42:58', '2025-07-30', 2, 1),
(53, 'Joao Evangelista', '762489', '528265', '44988490633', 6, 10, '2025-06-04 00:43:58', '2025-12-09', 2, 1),
(54, 'Pastor Luan', '223066', '749577', '55997272786', 6, 11, '2025-06-04 00:45:06', '2025-09-01', 2, 1),
(55, 'Wesley Sao jorge do ivai', 'Wesleytv', '5uCfJYR3Fn', '44988441119', 6, 11, '2025-06-04 00:46:18', '2026-05-13', 2, 1),
(56, 'Sogro Robinho', '928310', '512006', '44988266133', 6, 11, '2025-06-04 00:47:17', '2026-05-04', 2, 1),
(57, 'Fernando sao jorge do ivai', '281816', '958743', '44999804266', 6, 11, '2025-06-04 00:48:44', '2025-07-19', 2, 1),
(58, 'Valnei', '656201', '696884', '44988026512', 6, 9, '2025-06-04 00:49:38', '2025-07-07', 2, 1),
(59, 'Vanderlei sao jorge do ivai', '160659', '452203', '44988477067', 6, 11, '2025-06-04 00:50:31', '2026-03-16', 2, 1),
(60, 'Sidnei Maringa', '510842', '055307', '44999214326', 6, 11, '2025-06-04 00:53:16', '2026-05-06', 2, 1),
(61, 'Leandro sao jorge do ivai', '499601', '287081', '44988134272', 6, 11, '2025-06-04 00:55:02', '2026-01-12', 2, 1),
(62, 'Fran Bittar', '279683', '773317', '44998688386', 6, 9, '2025-06-04 00:55:56', '2025-07-23', 2, 1),
(63, 'Rogerio bebe', '692066', '423323', '43999320535', 6, 11, '2025-06-04 00:56:47', '2026-01-01', 2, 1),
(64, 'Joao cunhado Rafa', '359195', '913343', '44988463115', 6, 11, '2025-06-04 00:58:06', '2026-04-22', 2, 1),
(65, 'Luciana Irma lucio', '585543', '595396', '44988343940', 6, 11, '2025-06-04 00:59:10', '2026-01-21', 2, 1),
(66, 'Lucio Sao jorge do ivai', '232773', '210350', '44999212347', 6, 11, '2025-06-04 01:00:16', '2026-02-20', 2, 1),
(67, 'Gabriel sao jorge do ivai', '760341', '181627', '44988075261', 6, 11, '2025-06-04 01:01:05', '2025-10-21', 2, 1),
(68, 'Juliana sao jorge do ivai', '040578', '825702', '44988356474', 6, 11, '2025-06-04 01:02:00', '2026-03-29', 2, 1),
(69, 'Rafa Grafica', '499440', '559482', '44988166863', 6, 11, '2025-06-04 01:02:50', '2026-04-12', 2, 1),
(70, 'Hayane sao jorge do ivai', '856445', '575801', '44988111129', 6, 11, '2025-06-04 01:03:50', '2026-01-04', 2, 1),
(71, 'Renan sao jorge do ivai', '269266', '421954', '44988655993', 6, 11, '2025-06-04 01:04:56', '2025-08-04', 2, 1),
(72, 'Cristiane sao jorge do ivai', 'CristineTV', '91655272886', '44988285958', 6, 11, '2025-06-04 01:06:01', '2025-11-30', 2, 1),
(73, 'Diego sao jorge do ivai', '108964', '428622', '44988242753', 6, 11, '2025-06-04 01:07:00', '2025-11-20', 2, 1),
(74, 'Rosangela sao jorge do ivai', 'gianrosangela', 'Guanrosangela', '44999004588', 6, 11, '2025-06-04 01:08:13', '2025-09-24', 2, 1),
(75, 'Rogerio Vaguinho', '802504647', '610800276', '44988161300', 6, 11, '2025-06-04 23:28:29', '2025-10-04', 2, 1),
(77, 'Daniel Cabral', 'DanielCabral', 'd5gdeHK', '12991922937', 11, 5, '2025-06-05 13:55:28', '2025-07-13', 1, 1),
(78, 'Fabiana Brás', 'Fabianabras', '8754656', '12988265840', 11, 5, '2025-06-07 19:25:36', '2025-07-09', 1, 1),
(79, 'Jessica barbosa', '964374279', '462056511', '44988073288', 6, 11, '2025-06-09 22:14:25', '2025-12-10', 2, 1),
(80, 'Gustavo Neres', 'GustavoNeres', '82665373857', '73999302813', 10, 4, '2025-06-12 15:13:20', '2026-06-12', 1, 1),
(81, 'Adriano', 'Adriano0044', '8q72w6w627', '44997104397', 12, 5, '2025-06-13 00:58:20', '2025-07-13', 1, 1),
(82, 'Batore', '213462244', '811834070', '44988384514', 6, 11, '2025-06-28 16:12:30', '2026-06-15', 2, 1),
(83, 'Alecio', '590059585', '874156189', '44991829176', 6, 9, '2025-06-28 16:13:25', '2025-08-15', 2, 1),
(84, 'Vitoria', '338912818', '206516576', '11981776096', 6, 9, '2025-06-28 16:14:28', '2025-07-19', 2, 1),
(85, 'Thiago Facina', '468938223', '034433557', '44998964832', 6, 11, '2025-06-28 16:16:47', '2025-12-19', 2, 1),
(86, 'Sirlene mae samia', '570374721', '333382701', '73998304726', 6, 9, '2025-06-28 16:18:29', '2025-07-25', 2, 1),
(87, 'Samira', '306838957', '702442770', '79998390309', 6, 9, '2025-06-28 16:19:23', '2025-07-28', 2, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `whatsapp_predefinidas`
--

CREATE TABLE `whatsapp_predefinidas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `texto` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Despejando dados para a tabela `whatsapp_predefinidas`
--

INSERT INTO `whatsapp_predefinidas` (`id`, `titulo`, `texto`, `created_at`) VALUES
(1, 'Lembrete de Vencimento', 'Olá, seu plano ${nome} de ${valor_1} na ${nome} está próximo do vencimento.<br>\r\nO valor é R$ [valor].<br>\r\nPor favor, efetue o pagamento.', '2025-05-30 02:26:22'),
(2, 'Agradecimento', 'Obrigado por escolher a ${nome}!<br>\r\nSeu pagamento para o plano ${nome_plano} (R$ [valor]) foi confirmado.<br>\r\nAproveite seu acesso por ${valor_1}!', '2025-05-30 02:33:33'),
(3, 'Pagamento Vencido', 'Olá ${nome}, identificamos que seu pagamento de ${valor_1} está vencido. Por favor, regularize o quanto antes para evitar a suspensão do serviço.', '2025-06-02 00:46:30');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices de tabela `alugueis`
--
ALTER TABLE `alugueis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cliente_id` (`cliente_id`),
  ADD KEY `produto_id` (`produto_id`),
  ADD KEY `alugueis_ibfk_3` (`servico_id`);

--
-- Índices de tabela `carrinho_temp`
--
ALTER TABLE `carrinho_temp`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `fk_cadastrado_por` (`cadastrado_por`);

--
-- Índices de tabela `compras`
--
ALTER TABLE `compras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `compras_ibfk_1` (`cliente_id`),
  ADD KEY `compras_ibfk_2` (`produto_id`);

--
-- Índices de tabela `faturas`
--
ALTER TABLE `faturas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plano_id` (`plano_id`),
  ADD KEY `fk_faturas_cliente` (`user_id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `produtos_servicos`
--
ALTER TABLE `produtos_servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `servidores`
--
ALTER TABLE `servidores`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_servidores_clientes` (`cliente_id`);

--
-- Índices de tabela `template_users`
--
ALTER TABLE `template_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_cliente_titulo` (`cliente_id`,`titulo`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `usuario` (`usuario`),
  ADD KEY `fk_users_clientes` (`created_by`),
  ADD KEY `users_ibfk_1` (`servidor_id`),
  ADD KEY `fk_users_plano_id` (`plano_id`);

--
-- Índices de tabela `whatsapp_predefinidas`
--
ALTER TABLE `whatsapp_predefinidas`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `alugueis`
--
ALTER TABLE `alugueis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `carrinho_temp`
--
ALTER TABLE `carrinho_temp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `compras`
--
ALTER TABLE `compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `faturas`
--
ALTER TABLE `faturas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `produtos_servicos`
--
ALTER TABLE `produtos_servicos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `servidores`
--
ALTER TABLE `servidores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `template_users`
--
ALTER TABLE `template_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=88;

--
-- AUTO_INCREMENT de tabela `whatsapp_predefinidas`
--
ALTER TABLE `whatsapp_predefinidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `alugueis`
--
ALTER TABLE `alugueis`
  ADD CONSTRAINT `alugueis_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alugueis_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `alugueis_ibfk_3` FOREIGN KEY (`servico_id`) REFERENCES `produtos_servicos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cliente_id` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_produto_id` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `clientes`
--
ALTER TABLE `clientes`
  ADD CONSTRAINT `fk_cadastrado_por` FOREIGN KEY (`cadastrado_por`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `compras`
--
ALTER TABLE `compras`
  ADD CONSTRAINT `compras_ibfk_1` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `compras_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `faturas`
--
ALTER TABLE `faturas`
  ADD CONSTRAINT `faturas_ibfk_2` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`),
  ADD CONSTRAINT `fk_faturas_cliente` FOREIGN KEY (`user_id`) REFERENCES `clientes` (`id`);

--
-- Restrições para tabelas `servidores`
--
ALTER TABLE `servidores`
  ADD CONSTRAINT `fk_servidores_cliente_id` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_servidores_clientes` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_clientes` FOREIGN KEY (`created_by`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_users_plano_id` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`),
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`servidor_id`) REFERENCES `servidores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
