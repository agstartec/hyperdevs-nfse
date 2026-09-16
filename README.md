# hyperdevs/nfse

Biblioteca PHP para emitir, consultar e cancelar **NFS-e** através da **NFS-e Nacional**
(SEFIN/ADN — a plataforma unificada do governo para nota fiscal de serviço). Já inclui tudo
que é necessário para assinar o XML e autenticar com o certificado digital — **sem precisar
instalar nenhuma biblioteca extra do NFePHP**.

Este guia foi escrito para quem nunca integrou com a NFS-e Nacional antes. Siga os passos
na ordem.

> **Seu sistema tem vários clientes, cada um com sua própria prefeitura/certificado?**
> (ex.: um SaaS de gestão que emite nota em nome de cada empresa cadastrada) Os Passos 3 e 4
> abaixo usam um único certificado fixo no `.env` — ótimo para quem emite nota só para a própria
> empresa, mas não serve nesse caso. Pule direto para [Vários clientes (multi-tenant/SaaS)](#vários-clientes-multi-tenantsaas).

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
composer require hyperdevs/nfse
```

## Passo 2 — Publicar o arquivo de configuração

Se seu projeto é Laravel, o pacote já se registra sozinho (autodiscovery). Rode:

```bash
php artisan vendor:publish --tag=nfse-config
```

Isso cria o arquivo `config/nfse.php` na sua aplicação.

## Passo 3 — Configurar o `.env` da sua aplicação

> Esta configuração fixa **uma única** prefeitura/certificado para toda a aplicação — use-a se
> você emite nota apenas em nome da sua própria empresa. Se são vários clientes com dados
> próprios, vá direto para [Vários clientes (multi-tenant/SaaS)](#vários-clientes-multi-tenantsaas).

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
use Hyperdevs\Nfse\Application\DTO\Request\DpsRequest;
use Hyperdevs\Nfse\Application\DTO\Request\PrestadorRequest;
use Hyperdevs\Nfse\Application\DTO\Request\ServicoRequest;
use Hyperdevs\Nfse\Application\DTO\Request\TomadorRequest;
use Hyperdevs\Nfse\Application\Exception\ServiceException;
use Hyperdevs\Nfse\Application\Exception\ValidationException;
use Hyperdevs\Nfse\Facade\NfseNacionalFacade;

// 1. A "Facade" é o único objeto que você precisa usar. Em Laravel, injete ou resolva do container:
// (isto usa o certificado/prefeitura fixos do .env — para múltiplos clientes, veja a seção
// "Vários clientes (multi-tenant/SaaS)" mais abaixo)
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
use Hyperdevs\Nfse\Application\DTO\Request\EventoRequest;

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

## Vários clientes (multi-tenant/SaaS)

Se seu sistema emite nota **em nome de vários clientes** (cada um com sua própria prefeitura
e certificado digital — o cenário comum de um SaaS de gestão), **não** use o `.env` para isso:
prefeitura e certificado pertencem à conta de cada cliente, não à aplicação como um todo.

Use o `NfseManager` para montar uma Facade **por cliente, a cada requisição**, com os dados
vindos de onde quer que você os guarde (banco de dados, storage do certificado, etc.):

```php
use Hyperdevs\Nfse\Config\Config;
use Hyperdevs\Nfse\Http\Security\CertificateManager;
use Hyperdevs\Nfse\Http\Security\XmlSigner;
use Hyperdevs\Nfse\Provider\NfseManager;

// $cliente é o registro do seu banco (empresa/tenant que está emitindo a nota)
$certificateManager = CertificateManager::fromPfxFile(
    $cliente->caminho_certificado,
    $cliente->senha_certificado,
);

$facade = app(NfseManager::class)->make(
    config: new Config([
        'tpAmb' => $cliente->ambiente,       // 1 = Produção, 2 = Homologação
        'prefeitura' => $cliente->codigo_ibge,
    ]),
    certificateManager: $certificateManager,
    xmlSigner: new XmlSigner($certificateManager->getCertificate()),
);

$resposta = $facade->emitirDps($dpsRequest);
```

A partir daqui, use a `$facade` normalmente (`emitirDps`, `consultarPorChave`, `cancelar`, etc.),
igual ao Passo 4. O `NfseManager` não guarda estado entre clientes — cada chamada a `make()`
gera uma Facade isolada, então é seguro usar em uma aplicação com muitos clientes simultâneos.

> 💡 Guarde o certificado de cada cliente em local seguro (ex.: storage privado/criptografado),
> nunca versionado no código. O `.env` continua útil apenas para valores padrão/globais,
> como o tipo de ambiente (homologação/produção) da aplicação toda, se fizer sentido no seu caso.

## Usando fora do Laravel (PHP puro)

```php
use Hyperdevs\Nfse\Config\Config;
use Hyperdevs\Nfse\Facade\NfseNacionalFacade;
use Hyperdevs\Nfse\Http\Security\CertificateManager;
use Hyperdevs\Nfse\Http\Security\XmlSigner;

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
| `RuntimeException` ao usar `app(NfseNacionalFacade::class)` | Faltou configurar `NFSE_PREFEITURA`/`NFSE_CERTIFICADO_*` no `.env`. Se seu sistema tem vários clientes, isso é esperado — use `NfseManager::make()` (veja [Vários clientes (multi-tenant/SaaS)](#vários-clientes-multi-tenantsaas)) em vez da Facade padrão. |

Se você quiser usar uma fonte de certificado diferente de um arquivo `.pfx` local (ex.: um
cofre de segredos ou HSM), implemente `CertificateManagerInterface`/`XmlSignerInterface` e
monte-as você mesmo, passando para `NfseManager::make()` — o mesmo caminho usado no cenário
multi-tenant, ou passando direto para `NfseNacionalFacade::create()` fora do Laravel.

---

## Licença

MIT — veja [LICENSE](LICENSE). O aviso de copyright deve ser mantido em cópias/distribuições do código.
