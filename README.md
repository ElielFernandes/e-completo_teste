<h3>Gateway de pagamento PAGCOMPLETO.</h3>
Processamento do pagamento.

O processamento de pagamento possui duas classes, "Cartao" contida no arquivo Cartao.php, responsável por realizar as
requisições para a API do PAGCOMPLETO, e a classe "Dados", contida no arquivo Dados.php, responsável por fazer a comunicação com o banco de dados.
<hr/>

<h4>Classe Cartao:</h4>
Possui um único método chamada "requisicao_API" onde é necessário dois parâmetros.

exemplo:</br> 

**$resultado = $cartao_API->requisicao_API( array $pedido, string $Api_Key);**</br> 

**$pedido:** dados do pedido a ser processado pela API.</br> 
**$Api_Key:** Credencial que será fornecido atraves do header, necessaria para utilização da API.</br> 
**$resultado (object):** Resultado fornecido pela API sobre o processamento dos dados do pedido.</br> 

//Retorno caso a operação tenha sucesso.</br> 
$resultado  = [</br> 
&emsp;&emsp;'Error'=>Boolean,</br> 
&emsp;&emsp;'Transaction_code' =>String,</br>
&emsp;&emsp;'Message'=> String</br> 
&emsp;&emsp;'Date'=> String,</br> 
&emsp;&emsp;'Pedido'=> Object</br> 
];</br> 
// Retorno caso a operação não tenha sucesso.</br> 
$resultado  = [</br> 
&emsp;&emsp;'Error'=> Boolean,</br> 
&emsp;&emsp;'Message'=> String,</br> 
&emsp;&emsp;'Date'=> String,</br> 
&emsp;&emsp;'Pedido'=> Object</br> 
];</br> 
**Error:** se há erro ou não.</br> 
**Transaction_code:** código de retorno da transação (só retornará no momento da execução da transação).</br> 
**Message:** Mensagem de retorno.</br> 
**Date:** Data da operação.</br> 
**Pedido:** Dados do Pedido.</br> 
<hr/>

<h4>Classe Dados:</h4>

**Método: dados_API(),**</br>
Realiza uma requisição para o banco de dados e retorna todos os pedidos que possuem como requisito:

1)Gateway: PAGCOMPLETO,</br> 
2)Forma de pagamento:cartão de credito</br> 
3)Situação:aguardando pagamento.</br> 

Caso não possua dados com esses requisitos é retornado NULL.</br> 
As colunas retornadas são todas as necessarias para a requisição para a API, utilizadas no método requisicao_API(), da classe Cartão.</br> 
/-------------/

**Método: pedido_id_API(int $id),**</br>
Realiza uma requisição para o banco de dados e retorna apenas um pedido através do parâmentro $id passado para o método, onde é verificado os requisitos de pedido:</br> 

1)Gateway: PAGCOMPLETO,</br> 
2)Forma de pagamento:cartão de credito</br> 
3)Situação:aguardando pagamento.</br> 

Caso o pedido não possua os requisitos é retornado NULL.</br> 
As colunas retornadas são todas as necessárias para a requisição para a API, utilizadas no método requisicao_API(), da classe Cartão.</br> 
/-------------/

**Método: atualiza_status(object $resultado);**</br> 
Responsável por atualizar a situação do pedido no banco de dados.</br> 

**$resultado_status = $dados_API->atualiza_status($resultado);**</br> 

**$resultado:** resultado retornado pela API.</br> 
**$resultado_status (object):** resultado retornado pela atualização do banco de dados.</br> 

$resultado_status = [</br> 
&emsp;&emsp;'Error'=> Boolean,</br> 
&emsp;&emsp;'Message'=> String</br> 
];</br> 
**Error:** se há erro ou não.</br> 
**Message:** Mensagem de retorno.</br> 
