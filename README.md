# hyperevs/nfse

Biblioteca PHP para emitir, consultar e cancelar **NFS-e** através da **NFS-e Nacional**
(SEFIN/ADN — a plataforma unificada do governo para nota fiscal de serviço). Já inclui tudo
que é necessário para assinar o XML e autenticar com o certificado digital — **sem precisar
instalar nenhuma biblioteca extra do NFePHP**.

Este guia foi escrito para quem nunca integrou com a NFS-e Nacional antes. Siga os passos
na ordem.

---

## O que você precisa ter em mãos antes de começar

1. **PHP 8.3 ou superior**, com as extensões `dom`, `curl`, `zlib` e `openssl` habilitadas
   (a maioria das instalações já vem com elas).
2. **Um certificado digital A1** da empresa que vai emitir a nota, no formato `.pfx` ou `.p12`,
   e a senha dele. É esse arquivo que garante que só sua empresa pode emitir notas em seu nome.
   *(Certificado A3 — em token/cartão — não é suportado, pois exige hardware específico.)*
3. **O código IBGE da prefeitura/município** onde sua empresa está cadastrada (7 dígitos).
   Ex.: São Paulo/SP = `3550308`.

---

## Passo 1 — Instalar o pacote

```bash
composer require hyperevs/nfse
```

## Passo 2 — Publicar o arquivo de configuração

Se seu projeto é Laravel, o pacote já se registra sozinho (autodiscovery). Rode:

```bash
php artisan vendor:publish --tag=nfse-config
```

Isso cria o arquivo `config/nfse.php` na sua aplicação.

## Passo 3 — Configurar o `.env` da sua aplicação

Abra o `.env` do **seu projeto** (não do pacote) e adicione:

```dotenv
# 1 = Produção (nota valendo de verdade) | 2 = Homologação (ambiente de testes)
NFSE_TP_AMB=2

# Código IBGE da prefeitura da sua empresa (7 dígitos)
NFSE_PREFEITURA=3550308

# sefin ou adn — na dúvida, deixe "sefin"
NFSE_TIPO_API=sefin

# Caminho absoluto do certificado digital A1 e a senha dele
NFSE_CERTIFICADO_PATH=/caminho/absoluto/para/certificado.pfx
NFSE_CERTIFICADO_SENHA=senha-do-certificado
```

> ⚠️ **Comece sempre com `NFSE_TP_AMB=2` (homologação)**. Só mude para `1` (produção)
> depois de confirmar que as emissões de teste funcionam corretamente.

Com isso, o pacote já sabe ler seu certificado e assinar o XML sozinho — não precisa
escrever nenhum código de segurança.

## Passo 4 — Emitir sua primeira NFS-e (exemplo completo)

```php
use Hyperevs\Nfse\Application\DTO\Request\DpsRequest;
use Hyperevs\Nfse\Application\DTO\Request\PrestadorRequest;
use Hyperevs\Nfse\Application\DTO\Request\ServicoRequest;
use Hyperevs\Nfse\Application\DTO\Request\TomadorRequest;
use Hyperevs\Nfse\Application\Exception\ServiceException;
use Hyperevs\Nfse\Application\Exception\ValidationException;
use Hyperevs\Nfse\Facade\NfseNacionalFacade;

// 1. A "Facade" é o único objeto que você precisa usar. Em Laravel, injete ou resolva do container:
$facade = app(NfseNacionalFacade::class);

// 2. Monte os dados do prestador (quem está emitindo a nota — sua empresa)
$prestador = new PrestadorRequest(
    documento: '12345678000199',      // CNPJ da sua empresa (só números)
    isCnpj: true,
    inscricaoMunicipal: '123456',
    razaoSocial: 'Minha Empresa LTDA',
    telefone: '11999999999',
    email: 'contato@minhaempresa.com.br',
    logradouro: 'Rua Exemplo',
    numero: '100',
    complemento: null,
    bairro: 'Centro',
    codigoMunicipio: '3550308',        // mesmo código IBGE do .env
    uf: 'SP',
    cep: '01000000',
    regimeTributario: 1,               // 1 = Simples Nacional (consulte seu contador)
);

// 3. Monte os dados do tomador (cliente que está pagando pelo serviço)
$tomador = new TomadorRequest(
    documento: '98765432100',
    isCnpj: false,                     // false = CPF, true = CNPJ
    razaoSocial: 'Nome do Cliente',
    telefone: null,
    email: 'cliente@exemplo.com',
    logradouro: 'Av. Cliente',
    numero: '200',
    complemento: null,
    bairro: 'Bairro Cliente',
    codigoMunicipio: '3550308',
    uf: 'SP',
    cep: '01001000',
);

// 4. Monte os dados do serviço prestado
$servico = new ServicoRequest(
    discriminacao: 'Serviço de consultoria em TI',
    codigoTributacao: '0107',          // código do serviço conforme a LC 116
    valorServicos: 500.00,
    aliquotaIss: 2.0,                  // alíquota do ISS em % (ex.: 2%)
);

// 5. Junte tudo na DPS (Declaração de Prestação de Serviço — é o "pedido" de emissão)
$dpsRequest = new DpsRequest(
    tipoAmbiente: 2,                   // igual ao NFSE_TP_AMB do .env
    dataEmissao: date('c'),
    versaoAplicacao: '1.0.0',
    serie: 1,
    numero: 1,                         // número sequencial da sua nota — controle isso no seu banco
    dataCompetencia: date('Y-m-d'),
    tipoEmissao: 1,
    codigoMunicipioEmissor: '3550308',
    prestador: $prestador,
    servico: $servico,
    tomador: $tomador,
);

// 6. Envia para a SEFIN
try {
    $resposta = $facade->emitirDps($dpsRequest);

    if ($resposta->success) {
        echo "NFS-e emitida! Chave de acesso: {$resposta->chaveAcesso}\n";
    } else {
        echo "A SEFIN recusou a nota: {$resposta->mensagem}\n";
        // $resposta->erros traz a lista completa de erros retornados
    }
} catch (ValidationException $e) {
    // Os dados enviados têm algum problema (ex.: CNPJ inválido, campo obrigatório faltando)
    echo "Dados inválidos: {$e->getMessage()}\n";
} catch (ServiceException $e) {
    // Falha de comunicação com a SEFIN (rede, timeout, certificado, etc.)
    echo "Erro ao comunicar com a SEFIN: {$e->getMessage()}\n";
}
```

## Passo 5 — Consultar ou cancelar uma nota já emitida

```php
// Consultar pela chave de acesso (50 dígitos)
$nfse = $facade->consultarPorChave($resposta->chaveAcesso);

// Cancelar (usa a mesma Facade, com um EventoRequest)
use Hyperevs\Nfse\Application\DTO\Request\EventoRequest;

$evento = new EventoRequest(
    tipoAmbiente: 2,
    versaoAplicacao: '1.0.0',
    dataEvento: date('c'),
    chaveNfse: $resposta->chaveAcesso,
    tipoEvento: '101101',              // código do tipo de evento (ex.: cancelamento)
    codigoMotivo: '1',
    descricaoMotivo: 'Erro de emissão',
);

$facade->cancelar($evento);
```

## Usando fora do Laravel (PHP puro)

```php
use Hyperevs\Nfse\Config\Config;
use Hyperevs\Nfse\Facade\NfseNacionalFacade;
use Hyperevs\Nfse\Http\Security\CertificateManager;
use Hyperevs\Nfse\Http\Security\XmlSigner;

$certificateManager = CertificateManager::fromPfxFile('/caminho/certificado.pfx', 'senha');
$xmlSigner = new XmlSigner($certificateManager->getCertificate());

$facade = NfseNacionalFacade::create(
    config: new Config(['tpAmb' => 2, 'prefeitura' => '3550308']),
    certificateManager: $certificateManager,
    xmlSigner: $xmlSigner,
);
```

---

## Problemas comuns

| Sintoma | Causa provável |
|---|---|
| `CertificateExpiredException` | O certificado `.pfx` está vencido — gere um novo com seu emissor. |
| `ValidationException` ao emitir | Algum campo obrigatório do `DpsRequest` está errado ou faltando (veja a mensagem, ela aponta o campo). |
| `ServiceException` | Falha de rede, timeout, ou o certificado não corresponde ao CNPJ configurado na prefeitura. |
| Nada acontece / erro de binding | Confirme que `NFSE_CERTIFICADO_PATH` e `NFSE_CERTIFICADO_SENHA` estão no `.env` e que o caminho do arquivo existe no servidor. |

Se você quiser usar uma fonte de certificado diferente de um arquivo `.pfx` local (ex.: um
cofre de segredos ou HSM), implemente `CertificateManagerInterface`/`XmlSignerInterface` e
substitua o binding padrão no `ServiceProvider` da sua aplicação:

```php
$this->app->bind(\Hyperevs\Nfse\Http\Security\Contract\CertificateManagerInterface::class, MeuCertificateManager::class);
$this->app->bind(\Hyperevs\Nfse\Http\Security\Contract\XmlSignerInterface::class, MeuXmlSigner::class);
```

---

## Licença

MIT — veja [LICENSE](LICENSE). O aviso de copyright deve ser mantido em cópias/distribuições do código.
