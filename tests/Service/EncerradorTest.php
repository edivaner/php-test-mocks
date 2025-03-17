<?php

namespace Alura\Leilao\Tests\Service;

use Alura\Leilao\Dao\Leilao as LeilaoDao;
use Alura\Leilao\Model\Leilao;
use Alura\Leilao\Service\Encerrador;
use Alura\Leilao\Service\EnviadorEmail;
use PHPUnit\Framework\TestCase;


class EncerradorTest extends TestCase
{
    private $encerrador;
    private $leilaoDao;
    /* @var MockObject */
    private $enviadorEmailDao;

    private $leilao;
    private $variante;

    private $chamadas;

    protected function setUp(): void
    {
        $this->chamadas = [];

        $this->leilao = new Leilao('Fiat 147 0KM', new \DateTimeImmutable('8 days ago'));
        $this->variante = new Leilao('Variant 1972 0KM', new \DateTimeImmutable('10 days ago'));

        $this->leilaoDao = $this->createMock(LeilaoDao::class);
        $this->enviadorEmailDao = $this->createMock(EnviadorEmail::class);
        // $leilaoDao = $this->getMockBuilder(LeilaoDao::class)->setConstructorArgs([new LeilaoDao('sqlite::memory:')])->getMock();

        $this->leilaoDao->method('recuperarNaoFinalizados')->willReturn([$this->leilao, $this->variante]);
        $this->leilaoDao->method('recuperarFinalizados')->willReturn([$this->leilao, $this->variante]);
        // $this->leilaoDao->expects($this->exactly(2))->method('atualiza')->withConsecutive([$leilao], [$variante]); // executa 2 vezes um método. withConsecutive removido mno unit 11
        $this->leilaoDao->expects($this->exactly(2))->method('atualiza')->willReturnCallback(function ($arg) use (&$chamadas) {
            $this->chamadas[] = $arg;
        });

        $this->encerrador = new Encerrador($this->leilaoDao, $this->enviadorEmailDao);
    }

    public function testLeiloesComMaisDeUmaSemanaDevemSerEncerrados(){
        
        $this->encerrador->encerra();

        $leiloesFinalizados = $this->leilaoDao->recuperarFinalizados();

        $this->assertSame([$this->leilao, $this->variante], $this->chamadas);
        self::assertCount(2, $leiloesFinalizados);
        self::assertTrue($leiloesFinalizados[0]->estaFinalizado());
        self::assertTrue($leiloesFinalizados[1]->estaFinalizado());
        self::assertEquals('Fiat 147 0KM', $leiloesFinalizados[0]->recuperarDescricao());
        self::assertEquals('Variant 1972 0KM', $leiloesFinalizados[1]->recuperarDescricao());
    }

    public function testProcessoDeEncerramentoDeveContinuarMesmoOcorrendoExcecaoDeEmail(){
        $this->enviadorEmailDao->expects($this->exactly(2))->method('notificarTerminoLeilao')->willthrowException(new \DomainException('Falha ao enviar email'));
        $this->encerrador->encerra();
    }

    public function testSoDeveEnviarLeilaoPorEmailAposFinalizado(){
        $this->enviadorEmailDao->expects($this->exactly(2))->method('notificarTerminoLeilao')->willReturnCallback(function (Leilao $leilao){
            static::assertTrue($leilao->estaFinalizado());
        });

        $this->encerrador->encerra();
    }
    
}

?>