<?php
namespace modules\sga\triagem;

use \PDO;
use \Exception;
use \core\SGAContext;
use \core\util\Arrays;
use \core\model\util\Triagem;
use \core\controller\ModuleController;

/**
 * TriagemController
 *
 * @author rogeriolino
 */
class TriagemController extends ModuleController {
    
    public function __construct() {
        $this->title = _('Triagem');
        $this->subtitle = _('Gerencie a distribuíção das senhas da unidade atual');
    }
    
    private function servicos(\core\model\Unidade $unidade) {
        $query = $this->em()->createQuery("SELECT e FROM \core\model\ServicoUnidade e WHERE e.unidade = :unidade AND e.status = 1 ORDER BY e.nome");
        $query->setParameter('unidade', $unidade->getId());
        return $query->getResult();
    }

    public function index(SGAContext $context) {
        $unidade = $context->getUser()->getUnidade();
        $this->view()->assign('unidade', $unidade);
        if ($unidade) {
            $this->view()->assign('servicos', $this->servicos($unidade));
        }
        $query = $this->em()->createQuery("SELECT e FROM \core\model\Prioridade e ORDER BY e.nome");
        $this->view()->assign('prioridades', $query->getResult());
    }
    
    public function ajax_update(SGAContext $context) {
        $response = array('success' => false);
        $unidade = $context->getUnidade();
        if ($unidade) {
            $ids = Arrays::value($_GET, 'ids');
            $ids = Arrays::valuesToInt(explode(',', $ids));
            if (sizeof($ids)) {
                $conn = $this->em()->getConnection();
                $sql = "
                    SELECT 
                        id_serv as id, COUNT(*) as total 
                    FROM 
                        atendimentos
                    WHERE 
                        id_uni = :unidade AND 
                        id_serv IN (" . implode(',', $ids) . ")
                ";
                // total senhas do servico (qualquer status)
                $stmt = $conn->prepare($sql . " GROUP BY id_serv");
                $stmt->bindValue('unidade', $unidade->getId(), \PDO::PARAM_INT);
                $stmt->execute();
                $rs = $stmt->fetchAll();
                foreach ($rs as $r) {
                    $response[$r['id']] = array('total' => $r['total']);
                }
                // total senhas esperando
                $stmt = $conn->prepare($sql . " AND id_stat = :status GROUP BY id_serv");
                $stmt->bindValue('unidade', $unidade->getId(), \PDO::PARAM_INT);
                $stmt->bindValue('status', \core\model\Atendimento::SENHA_EMITIDA, \PDO::PARAM_INT);
                $stmt->execute();
                $rs = $stmt->fetchAll();
                foreach ($rs as $r) {
                    $response[$r['id']]['fila'] = $r['total'];
                }
                $response['success'] = true;
            }
        }
        $context->getResponse()->jsonResponse($response);
    }
    
    public function servico_info(SGAContext $context) {
        $info = array();
        if ($context->getRequest()->isPost()) {
            $id = (int) $context->getRequest()->getParameter('id');
            $servico = $this->em()->find("\core\model\Servico", $id);
            if ($servico) {
                $info['descricao'] = $servico->getDescricao();
                $info['subservicos'] = array();
                $query = $this->em()->createQuery("SELECT e FROM \core\model\Servico e WHERE e.mestre = :mestre ORDER BY e.nome");
                $query->setParameter('mestre', $servico->getId());
                $subservicos = $query->getResult();
                foreach ($subservicos as $s) {
                    $info['subservicos'][] = $s->getNome();
                }
            }
        }
        $context->getResponse()->jsonResponse($info);
    }
    
    public function distribui_senha(SGAContext $context) {
        $response = array('success' => false);
        $unidade = $context->getUnidade();
        try {
            if (!$unidade) {
                throw new Exception(_('Nenhum unidade escolhida'));
            }
            // verificando a prioridade
            $prioridade = (int) Arrays::value($_POST, 'prioridade');
            $query = $this->em()->createQuery("SELECT COUNT(e) as total FROM \core\model\Prioridade e WHERE e.id = :id");
            $query->setParameter('id', $prioridade);
            $rs = $query->getSingleResult();
            if ($rs['total'] == 0) {
                throw new Exception(_('Prioridade inválida'));
            }
            
            // verificando se o servico esta disponivel na unidade
            $servico = (int) Arrays::value($_POST, 'servico');
            $query = $this->em()->createQuery("SELECT COUNT(e) as total FROM \core\model\ServicoUnidade e WHERE e.unidade = :unidade AND e.servico = :servico");
            $query->setParameter('unidade', $unidade->getId());
            $query->setParameter('servico', $servico);
            $rs = $query->getSingleResult();
            if ($rs['total'] == 0) {
                throw new Exception(_('Serviço não disponível para a unidade atual'));
            }
            
            $conn = $this->em()->getConnection();
            $stmt = $conn->prepare(" 
                INSERT INTO atendimentos
                (id_uni, id_serv, id_pri, id_stat, nm_cli, ident_cli, num_guiche, dt_cheg, num_senha)
                -- select dentro do insert para garantir atomicidade
                SELECT
                    :id_uni, :id_serv, :id_pri, :id_stat, :nm_cli, :ident_cli, :num_guiche, :dt_cheg, 
                    COALESCE(
                        (
                            SELECT TOP 1
                                num_senha
                            FROM
                                atendimentos a
                            WHERE
                                a.id_uni = :id_uni
                            ORDER BY
                                num_senha DESC
                        ) , 0) + 1
            ");
            $stmt->bindValue('id_uni', $unidade->getId(), PDO::PARAM_INT);
            $stmt->bindValue('id_serv', $servico, PDO::PARAM_INT);
            $stmt->bindValue('id_pri', $prioridade, PDO::PARAM_INT);
            $stmt->bindValue('id_stat', \core\model\Atendimento::SENHA_EMITIDA, PDO::PARAM_INT);
            $stmt->bindValue('nm_cli', Arrays::value($_POST, 'cli_nome', ''), PDO::PARAM_STR);
            $stmt->bindValue('ident_cli', Arrays::value($_POST, 'cli_doc', ''), PDO::PARAM_STR);
            $stmt->bindValue('num_guiche', '', PDO::PARAM_INT);
            $stmt->bindValue('dt_cheg', date('Y-m-d H:i:s'), PDO::PARAM_STR);
            $response['success'] = ($stmt->execute() == true);
        } catch (Exception $e) {
            $response['success'] = false;
            $response['message'] = $e->getMessage();
        }
        $context->getResponse()->jsonResponse($response);
    }
    
}
