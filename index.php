<?php
require 'autoload.php';
require 'config.php';

use \banco\Dados;
use \API\Cartao;

$Api_Key = 'Api_key';

$dados_API = new Dados($driver);
$cartao_API = new Cartao();

/*
Exemplo de busca por um pedido pelo id, com os requisitos:
1)Gateway: PAGCOMPLETO,
2)Forma de pagamento:cartão de credito
3)Situação:aguardando pagamento.
enviando os dados para a API e atualizando o banco de dados
*/
$pedido = $dados_API->pedido_id_API(98302); //busca por um pedido através do Id.
//if($pedido){
    $resultado_requisicao = $cartao_API->requisicao_API($pedido, $Api_Key); //envia o pedido para API 
    $resultado_status = $dados_API->atualiza_status($resultado_requisicao); /* envia a resposta 
    da API e o pedido para atualização do banco.*/

    echo $resultado_requisicao->Error ? 
        'API:'.$resultado_requisicao->Message : 
        'API:'.$pedido['external_order_id'].'-'.$resultado_requisicao->Message;
    echo '- Banco:'.$resultado_status->Message."</br>";
//}

//-----------------------------------------------------------------------------------------------------

/*
Exemplo de busca por todos os dados do banco com os requisitos:
1)Gateway: PAGCOMPLETO,
2)Forma de pagamento:cartão de credito
3)Situação:aguardando pagamento.
enviando os dados para a API e atualizando o banco de dados.
*/
$dados = $dados_API->dados_API(); //Busca por todos os pedidos do banco com os requisistos.
if($dados) // Verifica se foram encontrados dados
{
    foreach($dados as $item){
        $resultado_requisicao = $cartao_API->requisicao_API($item, $Api_Key);//envia o pedido para API 
        $resultado_status = $dados_API->atualiza_status($resultado_requisicao);/*envia a resposta da API e 
        o pedido para atualização do banco.*/
        
        echo $resultado_requisicao->Error ? 
            'API:'.$resultado_requisicao->Message : 
            'API:'.$item['external_order_id'].'-'.$resultado_requisicao->Message; 
        echo '- Banco:'.$resultado_status->Message."</br>";   
    }
}
