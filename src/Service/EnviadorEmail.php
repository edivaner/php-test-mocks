<?php

namespace Alura\Leilao\Service;

use Alura\Leilao\Model\Leilao;

class EnviadorEmail
{
    public function notificarTerminoLeilao(Leilao $leilao) : void
    {
        $enviouComSucesso = \mail(
            to: 'edivanerfernandes18@gmail.com',
            subject: 'Leilão encerrado',
            message: "O leilão {$leilao->recuperarDescricao()} terminou!"
        );

        if(!$enviouComSucesso){
            throw new \DomainException('Falha ao enviar email');
        }
    }
}


?>