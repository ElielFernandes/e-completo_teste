<?php

namespace API;

class Cartao{

    private $ch;

    Public const BASE_URI = 'https://api11.ecompleto.com.br';
    Public const ENDPOINT = '/exams/processTransaction';

    public function requisicao_API( array $dados = NULL, string $Api_Key = NULL  ){
        
        $header = [
            'Authorization:'.$Api_Key,
            'Content-Type: application/json'
        ];
        //altera o formado da data de vencimento do cartão para enviar para a API.
        if(isset($dados['card_expiration_date'])){
            $data_cartao = $dados['card_expiration_date'];
            $data_cartao = $data_cartao[5].$data_cartao[6].$data_cartao[2].$data_cartao[3];
        }else{
            $data_cartao = NULL;
        }
        //Estrutuda dos dados necessarios para o processamento da API.
        $dados = [
            "external_order_id"=> isset($dados['external_order_id'])?$dados['external_order_id']: NULL ,
            "amount"=> isset($dados['amount'])? floatval($dados['amount']):NULL,
            "card_number"=> isset($dados['card_number'])?$dados['card_number']:NULL,
            "card_cvv"=> isset($dados['card_cvv'])?strval($dados['card_cvv']):NULL,
            "card_expiration_date"=> $data_cartao,
            "card_holder_name"=> isset($dados['card_holder_name'])?$dados['card_holder_name']:NULL,
            "customer"=> [
                "external_id"=> isset($dados['external_id'])?$dados['external_id']:NULL,
                "name"=> isset($dados['name'])?$dados['name']:NULL,
                "type"=> isset($dados['type'])?($dados['type']=='F'?'individual':'corporation'):NULL,
                "email"=> isset($dados['email'])?$dados['email']:NULL,
                    "documents"=> [
                    "type"=> isset($dados['document_type'])?($dados['document_type']=='F'?'cpf':'cnpj'):NULL,
                    "number"=> isset($dados['document_number'])?$dados['document_number']:NULL
                    ],
                "birthday"=>isset($dados['birthday'])? $dados['birthday']:NULL
            ]  
        ];

        $this->ch = curl_init();

        curl_setopt_array($this->ch,[
            CURLOPT_URL => self::BASE_URI.self::ENDPOINT,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $header,
            CURLOPT_POSTFIELDS => json_encode($dados),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS
        ]);

        $resultado = curl_exec($this->ch);
        $resultado = json_decode($resultado);
        //data do processamento da operação
        date_default_timezone_set('America/Sao_Paulo');
        $data = date('Y-m-d');
            
        curl_close($this->ch);

        // Retorno caso a operação tenha sucesso.
        if(isset($resultado->Transaction_code)){
            $resultado  = [
                'Error'=>$resultado->Error,
                'Transaction_code' =>$resultado->Transaction_code,
                'Message'=> $resultado->Message,
                'Date'=>$data,
                'Pedido'=> (object) $dados
            ];
        }
        // Retorno caso a operação não tenha sucesso.
        else{
            $resultado  = [
                'Error'=>$resultado->Error,
                'Message'=> $resultado->Message,
                'Date'=>$data,
                'Pedido'=>(object) $dados
            ];
        }
        return (object) $resultado;
    } 
}