<?php

namespace banco;

class Dados{

    private $pdo;

    public const PAGTO_APROVADO = '00'; //Pagamento Aprovado.
    public const PAGTO_ANALISE_CREDITO = '01';//Pagamento em Análise de crédito.
    public const PAGTO_ESTORNADO= '02';//Pagamento Estornado
    public const PAGTO_RECUSADO_CHARGEBACK = '03';//Pagamento Recusado. Alto risco de chargeback.
    public const PAGTO_RECUSADO = '04';// Pagamento Recusado. Cartão sem crédito disponível.

    //pedido situação
    public const AGUARDANDO_PAGAMENTO = 1; //Aguardando Pagamento
    public const PAGAMENTO_IDENTIFICADO = 2; //Pagamento identificado
    public const PEDIDO_CANCELADO = 3; //Pedido Cancelado
    
    //formas de pagamento
    public const FORMA_PAGAMENTO = 3; // Cartão de Crédito

    //gateways
    public const GATEWAY= 1; // PAGCOMPLETO
    
    public function __construct($driver){

        $this->pdo = $driver;
    }
    public function dados_API(){
        /*
        Busca todos os dados do banco necessarios para enviar para API, filtrando os requisistos:
        1)Gateway: PAGCOMPLETO,
        2)Forma de pagamento:cartão de credito
        3)Situação:aguardando pagamento.
        */
        $sql_pedidos = $this->pdo->prepare(
        "SELECT 
        pedidos_pagamentos.id_pedido AS external_order_id,
        pedidos.valor_total AS amount,
        pedidos_pagamentos.num_cartao AS card_number,
        pedidos_pagamentos.codigo_verificacao AS card_cvv,
        pedidos_pagamentos.vencimento AS card_expiration_date,
        pedidos_pagamentos.nome_portador AS card_holder_name,
        clientes.id AS external_id,
        clientes.nome AS name,
        clientes.tipo_pessoa AS type,
        clientes.email AS email,
        clientes.tipo_pessoa AS document_type,
        clientes.cpf_cnpj AS document_number,
        clientes.data_nasc as birthday
        FROM pedidos 
        INNER JOIN pedidos_pagamentos ON 
        pedidos.id = pedidos_pagamentos.id_pedido AND pedidos.id_situacao = :situacao AND pedidos_pagamentos.id_formapagto = :forma_pagamento
        INNER JOIN lojas_gateway ON 
        pedidos.id_loja = lojas_gateway.id_loja AND lojas_gateway.id_gateway = :gateway
        INNER JOIN clientes ON pedidos.id_cliente = clientes.id");

        $sql_pedidos->bindValue(':situacao', self::AGUARDANDO_PAGAMENTO);
        $sql_pedidos->bindValue(':forma_pagamento', self::FORMA_PAGAMENTO );
        $sql_pedidos->bindValue(':gateway', self::GATEWAY );
        $sql_pedidos->execute();

        if($sql_pedidos->rowCount()>0){

            $dados =  $sql_pedidos->fetchAll();
            return $dados;//retorna os pedidos, se encontrado.
        }
        else{

            return NULL; //retorna NULL caso os pedidos não tenham sido encontrados ou não tenham atendido os requisitos.
        }
    }
    public function pedido_id_API(int $id){
        /*
        Busca um pedido no banco de dados com todos os dados necessarios para enviar para API, 
        filtrando os requisistos:
        1)Gateway: PAGCOMPLETO,
        2)Forma de pagamento:cartão de credito
        3)Situação:aguardando pagamento.
        */
        $sql_pedidos = $this->pdo->prepare(
        "SELECT 
        pedidos_pagamentos.id_pedido AS external_order_id,
        pedidos.valor_total AS amount,
        pedidos_pagamentos.num_cartao AS card_number,
        pedidos_pagamentos.codigo_verificacao AS card_cvv,
        pedidos_pagamentos.vencimento AS card_expiration_date,
        pedidos_pagamentos.nome_portador AS card_holder_name,
        clientes.id AS external_id,
        clientes.nome AS name,
        clientes.tipo_pessoa AS type,
        clientes.email AS email,
        clientes.tipo_pessoa AS document_type,
        clientes.cpf_cnpj AS document_number,
        clientes.data_nasc as birthday
        FROM pedidos 
        INNER JOIN pedidos_pagamentos ON pedidos.id =:id AND
        pedidos.id = pedidos_pagamentos.id_pedido AND pedidos.id_situacao = :situacao AND pedidos_pagamentos.id_formapagto = :forma_pagamento
        INNER JOIN lojas_gateway ON 
        pedidos.id_loja = lojas_gateway.id_loja AND lojas_gateway.id_gateway = :gateway
        INNER JOIN clientes ON pedidos.id_cliente = clientes.id");

        $sql_pedidos->bindValue(':id', $id);
        $sql_pedidos->bindValue(':situacao', self::AGUARDANDO_PAGAMENTO);
        $sql_pedidos->bindValue(':forma_pagamento', self::FORMA_PAGAMENTO);
        $sql_pedidos->bindValue(':gateway', self::GATEWAY);
        $sql_pedidos->execute();
    
        if($sql_pedidos->rowCount()>0){
            $pedido =  $sql_pedidos->fetch();
            return $pedido; //retorna o pedido, se encontrado.
        }
        else{
            return NULL; // retorna NULL caso pedido não tenha sido encontrado ou não tenha atendido os requisitos.
        }
    }
    private function verifica($transaction_code){

        if($transaction_code == self::PAGTO_APROVADO){
            return self::PAGAMENTO_IDENTIFICADO;

        }else if($transaction_code == self::PAGTO_ANALISE_CREDITO){
            return self::AGUARDANDO_PAGAMENTO;

        }else if($transaction_code == self::PAGTO_ESTORNADO 
        || $transaction_code == self::PAGTO_RECUSADO_CHARGEBACK 
        || $transaction_code == self::PAGTO_RECUSADO){
            return self::PEDIDO_CANCELADO ;
        }
    }
    public function atualiza_status(object $resultado =NULL){     
    //Verifica o resultado fornecido pela API, e atualiza o banco de dados de acordo com cada resultado.
        
        if($resultado->Pedido->external_order_id){
            if(!$resultado->Error && isset($resultado->Transaction_code)){
                $sql = $this->pdo->prepare("UPDATE pedidos_pagamentos 
                SET retorno_intermediador = :retorno_intermediador,
                    data_processamento = :data_processamento  
                WHERE id_pedido = :id");
                $sql->bindValue(':retorno_intermediador', $resultado->Message); 
                $sql->bindValue(':data_processamento', $resultado->Date); 
                $sql->bindValue(':id',$resultado->Pedido->external_order_id);
                $sql->execute();

                $sql = $this->pdo->prepare("UPDATE pedidos SET id_situacao = :id_situacao WHERE id = :id");
                $sql->bindValue(':id_situacao',$this->verifica($resultado->Transaction_code));  
                $sql->bindValue(':id',$resultado->Pedido->external_order_id);
                $sql->execute();

                $retorno  = [
                    'Error'=>false,
                    'Message'=> 'Pedido atualizado.'
                ];
                return (object) $retorno;
            }
            else{
                $retorno  = [
                    'Error'=>true,
                    'Message'=> 'Erro na requisição para API.'
                ];
                return (object) $retorno;
            }
        }
        else{
            $retorno  = [
                'Error'=>true,
                'Message'=> 'Pedido não encontrado.'
            ];
            return (object) $retorno;
        }
    }
}