import http.server
import socketserver
import json
import os
import sys
import socket

PORT = 8000
DATA_FILE = "dados_elegancia.json"

# Dados iniciais padronizados
PRODUTOS_INICIAIS = [
    {
        "id": 1,
        "nome": "Camisa Social Slim Premium",
        "preco": 159.90,
        "estoque": 42,
        "imagemUrl": "https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=600&q=80",
        "categoria": "Camisas",
        "tamanho": "M"
    },
    {
        "id": 2,
        "nome": "Calça Alfaiataria Italiana",
        "preco": 199.90,
        "estoque": 28,
        "imagemUrl": "https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=600&q=80",
        "categoria": "Calças",
        "tamanho": "42"
    },
    {
        "id": 3,
        "nome": "Vestido de Festa Ateliê Rose",
        "preco": 450.00,
        "estoque": 15,
        "imagemUrl": "https://images.unsplash.com/photo-1566174053879-31528523f8ae?auto=format&fit=crop&w=600&q=80",
        "categoria": "Vestidos",
        "tamanho": "P"
    },
    {
        "id": 4,
        "nome": "Blazer Executivo Linho",
        "preco": 299.90,
        "estoque": 18,
        "imagemUrl": "https://images.unsplash.com/photo-1591047139829-d91aecb6caea?auto=format&fit=crop&w=600&q=80",
        "categoria": "Casacos",
        "tamanho": "G"
    },
    {
        "id": 5,
        "nome": "Sapato Oxford Masculino Couro",
        "preco": 320.00,
        "estoque": 8,
        "imagemUrl": "https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=600&q=80",
        "categoria": "Calçados",
        "tamanho": "41"
    },
    {
        "id": 6,
        "nome": "Cinto de Couro Nobre Sela",
        "preco": 89.90,
        "estoque": 35,
        "imagemUrl": "https://images.unsplash.com/photo-1624222247344-550fb8ecf7db?auto=format&fit=crop&w=600&q=80",
        "categoria": "Acessórios",
        "tamanho": "Único"
    },
    {
        "id": 7,
        "nome": "Saia Midi Plissada Coral",
        "preco": 179.90,
        "estoque": 22,
        "imagemUrl": "https://images.unsplash.com/photo-1583391733956-3750e0ff4e8b?auto=format&fit=crop&w=600&q=80",
        "categoria": "Saias",
        "tamanho": "M"
    }
]

CLIENTES_INICIAIS = [
    {"id": 1, "nome": "Consumidor Final", "telefone": "", "cpf": ""},
    {"id": 2, "nome": "Alice Monteverde", "telefone": "11988887777", "cpf": "12345678901"},
    {"id": 3, "nome": "Roberto Guimarães", "telefone": "21977776666", "cpf": "98765432109"},
    {"id": 4, "nome": "Eduarda Castanho", "telefone": "31966665555", "cpf": "45678912344"}
]

VENDAS_INICIAIS = [
    {
        "id": 1,
        "clienteNome": "Alice Monteverde",
        "total": 359.80,
        "desconto": 40.00,
        "dataCriacao": "2026-06-02T10:15:00Z",
        "itens": [
            {"idProduto": 1, "nomeProduto": "Camisa Social Slim Premium", "quantidade": 1, "precoUnitario": 159.90},
            {"idProduto": 2, "nomeProduto": "Calça Alfaiataria Italiana", "quantidade": 1, "precoUnitario": 199.90}
        ]
    }
]

def carregar_dados():
    if os.path.exists(DATA_FILE):
        try:
            with open(DATA_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            print(f"Erro ao ler banco local: {e}")
    
    # Se não existir, salva e retorna os dados originais
    dados = {
        "produtos": PRODUTOS_INICIAIS,
        "clientes": CLIENTES_INICIAIS,
        "vendas": VENDAS_INICIAIS
    }
    salvar_dados(dados)
    return dados

def salvar_dados(dados):
    try:
        with open(DATA_FILE, "w", encoding="utf-8") as f:
            json.dump(dados, f, indent=4, ensure_ascii=False)
        return True
    except Exception as e:
        print(f"Erro ao salvar dados localmente: {e}")
        return False

class EleganciaRequestHandler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        # Garante CORS habilitado para desenvolvimento se necessário
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type')
        super().end_headers()

    def do_GET(self):
        # API de Carregamento de Estado do Sistema
        if self.path == "/api/state":
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.end_headers()
            dados = carregar_dados()
            self.wfile.write(json.dumps(dados).encode("utf-8"))
            return
            
        # Qualquer outra rota GET serve nossa página index html unificada
        self.send_response(200)
        self.send_header("Content-Type", "text/html; charset=utf-8")
        self.end_headers()
        self.wfile.write(HTML_UI.encode("utf-8"))

    def do_POST(self):
        if self.path == "/api/state":
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            try:
                dados_atualizados = json.loads(post_data.decode('utf-8'))
                
                # Sincroniza e insiste os dados persistidos no JSON local
                sucesso = salvar_dados(dados_atualizados)
                
                self.send_response(200 if sucesso else 500)
                self.send_header("Content-Type", "application/json")
                self.end_headers()
                self.wfile.write(json.dumps({"status": "success" if sucesso else "error"}).encode('utf-8'))
            except Exception as e:
                self.send_response(400)
                self.send_header("Content-Type", "application/json")
                self.end_headers()
                self.wfile.write(json.dumps({"status": "error", "message": str(e)}).encode('utf-8'))
            return

    def do_OPTIONS(self):
        self.send_response(200)
        self.end_headers()

class EleganciaTCPServer(socketserver.TCPServer):
    allow_reuse_address = True

HTML_UI = r"""<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Comercial</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['"Playfair Display"', 'ui-serif', 'Georgia', 'serif'],
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
                        mono: ['SFMono-Regular', 'Menlo', 'monospace'],
                    },
                    colors: {
                        ebony: '#414833',
                        reseda: '#737A5D',
                        sage: '#A4AC86',
                        dun: '#CCBFA3',
                        bone: '#EBE3D2',
                    }
                }
            }
        }
    </script>
    <style>
        .scrollbar-none::-webkit-scrollbar { display: none; }
        .scrollbar-none { -ms-overflow-style: none; scrollbar-width: none; }
        body { background-color: #FAF9F6; text-rendering: optimizeLegibility; -webkit-font-smoothing: antialiased; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        
        @keyframes scaleUp {
            from { opacity: 0; transform: scale(0.96) translateY(5px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .animate-scale-up { animation: scaleUp 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;

        const TEMPLATE_IMAGENS = [
            { nome: "Saia Jeans", url: "https://misleading-indigo-rrfktsvo.edgeone.app/SAIA.jpg" },
            { nome: "Bolsa Azul Elegante", url: "https://decent-sapphire-pv9iwk9o.edgeone.app/bolsa.jpg" },
            { nome: "Oculos Elegante", url: "https://big-amber-aonkcumd.edgeone.app/oculos.jpg" },
            { nome: "Tenis Adidas Vermelho", url: "https://visible-white-bpzhicio.edgeone.app/tenis.jpg" },
            { nome: "Calça Jeans Preta", url: "https://devoted-salmon-wygt31hm.edgeone.app/calca.jpg" },
            { nome: "Vestido Off-White Curto", url: "https://limited-orange-cz77dz82.edgeone.app/vestido.jpg" },
            { nome: "Vestido Florido", url: "https://incredible-lavender-aoqkgml2.edgeone.app/vestido2.jpg" },
            { nome: "Calça Moletom Cinza", url: "https://sound-bronze-og81vhsv.edgeone.app/cal\u00e7a%202.jpg" },
            { nome: "Salto Alto Preto", url: "https://wandering-scarlet-6oj4heo4.edgeone.app/salto.jpg" },
            { nome: "Casaco Couro Vermelho", url: "https://fundamental-bronze-dxejkhvn.edgeone.app/casaco2.jpg" },
            { nome: "Bolsa Azul Grande", url: "https://uninterested-aquamarine-m4ergnn6.edgeone.app/bolsa2.jpg" },
            { nome: "Saia Branca Midi", url: "https://numerous-apricot-pzyxj1tv.edgeone.app/saia%202.jpg" }
        ];

        const USERS = [
            { id: 1, usuario: "maria", nome: "", cargo: "gerente" },
            { id: 2, usuario: "carlos", nome: "", cargo: "vendedor" },
            { id: 3, usuario: "patricia", nome: "", cargo: "estoquista" }
        ];

        // Componente auxiliar para forçar a renderização dos ícones do Lucide
        function TriggerIcons() {
            useEffect(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
            return null;
        }

        // --- HISTÓRICO / TOAST ---
        function Toast({ texto, tipo, onClose }) {
            useEffect(() => {
                const clock = setTimeout(onClose, 3000);
                return () => clearTimeout(clock);
            }, [onClose]);

            const isErro = tipo === 'erro';
            return (
                <div className={`fixed top-6 right-6 z-50 p-4 rounded-xl shadow-xl flex items-center gap-3 border transition-all text-left animate-scale-up ${
                    isErro ? 'bg-red-950 border-red-900/40 text-red-200' : 'bg-stone-900 border-stone-800 text-white'
                }`}>
                    <i data-lucide={isErro ? "alert-triangle" : "check-circle"} className={isErro ? "text-red-400 shrink-0" : "text-[#A4AC86] shrink-0"}></i>
                    <div>
                        <p className={`font-bold uppercase tracking-wider text-[10px] ${isErro ? 'text-red-450' : 'text-[#A4AC86]'}`}>
                            {isErro ? 'Atenção' : 'Operação Sucesso'}
                        </p>
                        <p className="text-stone-300 text-xs font-semibold mt-0.5">{texto}</p>
                    </div>
                </div>
            );
        }

        // --- DASHBOARD ---
        function Dashboard({ produtos, vendas }) {
            const totalFaturado = useMemo(() => vendas.reduce((acc, v) => acc + v.total, 0), [vendas]);
            const totalItensVendidos = useMemo(() => vendas.reduce((acc, v) => acc + v.itens.reduce((sum, item) => sum + item.quantidade, 0), 0), [vendas]);
            const estoqueBaixoCount = useMemo(() => produtos.filter(p => p.estoque < 10).length, [produtos]);

            const dadosGrafico = useMemo(() => {
                const vendasPorCategoria = {};
                let totalSoma = 0;

                vendas.forEach(venda => {
                    venda.itens.forEach(item => {
                        const prod = produtos.find(p => p.id === item.idProduto);
                        const categoria = prod?.categoria || "Outros";
                        const valorItem = item.quantidade * item.precoUnitario;
                        vendasPorCategoria[categoria] = (vendasPorCategoria[categoria] || 0) + valorItem;
                        totalSoma += valorItem;
                    });
                });

                const categoriasValidas = Object.keys(vendasPorCategoria).length > 0;
                const mapaFoco = categoriasValidas ? vendasPorCategoria : {
                    "Vestidos": 450.00,
                    "Calças": 199.90,
                    "Camisas": 159.90,
                    "Calçados": 320.00,
                    "Acessórios": 89.90
                };
                const divisor = categoriasValidas ? totalSoma : (450+199.9+159.9+320+89.9);

                return Object.keys(mapaFoco).map(cat => {
                    const valor = mapaFoco[cat];
                    const porcentagem = divisor > 0 ? (valor / divisor) * 100 : 0;
                    return { categoria: cat, valor, porcentagem };
                }).sort((a, b) => b.valor - a.valor);
            }, [vendas, produtos]);

            return (
                <div className="space-y-8 animate-fade-in text-left">
                    <div>
                        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Painel de Desempenho</h1>
                        <p className="text-slate-500 text-sm mt-1">Estatísticas comerciais e participação proporcional.</p>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div className="bg-white p-6 rounded-xl shadow-xs border border-slate-150 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Faturamento</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1 font-mono">
                                    R$ {totalFaturado.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                </h3>
                                <span className="text-[10px] text-emerald-600 flex items-center gap-1 mt-1 font-semibold">
                                    <i data-lucide="arrow-up-right" className="w-3.5 h-3.5"></i> +12% esta semana
                                </span>
                            </div>
                            <div className="p-3 bg-bone/30 text-ebony rounded-xl">
                                <i data-lucide="dollar-sign"></i>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl shadow-xs border border-slate-150 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Vendas</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1">{vendas.length} pedidos</h3>
                                <span className="text-[10px] text-zinc-500 flex items-center gap-1 mt-1 font-mono">
                                    Média: R$ {(vendas.length > 0 ? totalFaturado / vendas.length : 0).toFixed(2)}
                                </span>
                            </div>
                            <div className="p-3 bg-bone/30 text-ebony rounded-xl">
                                <i data-lucide="shopping-bag"></i>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl shadow-xs border border-slate-150 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Peças</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1">{totalItensVendidos} peças</h3>
                                <span className="text-[10px] text-slate-400 flex items-center gap-1 mt-1 font-semibold">
                                    Linha premium ativa
                                </span>
                            </div>
                            <div className="p-3 bg-bone/30 text-ebony rounded-xl">
                                <i data-lucide="package"></i>
                            </div>
                        </div>

                        <div className={`p-6 rounded-xl shadow-xs border transition ${estoqueBaixoCount > 0 ? 'bg-red-50/50 border-red-100' : 'bg-white border-slate-150'} flex items-center justify-between`}>
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Estoque Baixo</span>
                                <h3 className={`text-xl font-bold mt-1 ${estoqueBaixoCount > 0 ? 'text-red-700' : 'text-slate-800'}`}>
                                    {estoqueBaixoCount} itens
                                </h3>
                                <span className="text-[10px] text-red-600 flex items-center gap-1 mt-1 font-semibold">
                                    {estoqueBaixoCount > 0 ? 'Reposição necessária' : 'Estoque estável'}
                                </span>
                            </div>
                            <div className={`p-3 rounded-xl ${estoqueBaixoCount > 0 ? 'bg-red-105 text-red-700' : 'bg-zinc-50 text-zinc-400'}`}>
                                <i data-lucide="alert-triangle"></i>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="bg-white p-6 rounded-xl border border-slate-150 lg:col-span-2 flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 className="text-lg font-serif text-slate-800 font-bold">Distribuição de Receitas (%)</h3>
                                        <p className="text-slate-400 text-xs">Divisão proporcional do faturamento.</p>
                                    </div>
                                    <div className="flex items-center gap-1 px-3 py-1 bg-bone text-ebony rounded-full text-xs font-bold border border-dun/30">
                                        <i data-lucide="trending-up" className="w-3.5 h-3.5"></i>
                                        <span>Proporção Real</span>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {dadosGrafico.map((item, idx) => (
                                        <div key={item.categoria}>
                                            <div className="flex justify-between text-xs font-semibold text-slate-700 mb-1">
                                                <span>{item.categoria}</span>
                                                <div className="font-mono text-slate-500">
                                                    <span className="text-slate-800 font-bold mr-2">{item.porcentagem.toFixed(1)}%</span>
                                                    <span>R$ {item.valor.toFixed(2)}</span>
                                                </div>
                                            </div>
                                            <div className="w-full bg-slate-50 border border-slate-100 rounded-full h-3.5 overflow-hidden">
                                                <div 
                                                    style={{ width: `${item.porcentagem}%` }} 
                                                    className="bg-reseda h-full rounded-full transition-all duration-1000 ease-out"
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl border border-slate-151 flex flex-col justify-between">
                            <div>
                                <h3 className="text-lg font-serif text-slate-800 font-bold">Participação</h3>
                                <p className="text-slate-400 text-xs mb-4">Gráfico percentual estilizado.</p>

                                <div className="relative flex justify-center items-center py-6">
                                    <svg width="140" height="140" viewBox="0 0 42 42" className="transform -rotate-90">
                                        <circle cx="21" cy="21" r="15.915" fill="none" stroke="#f1f5f9" strokeWidth="5.5" />
                                        {(() => {
                                            let ac = 0;
                                            return dadosGrafico.map((item, idx) => {
                                                const array = `${item.porcentagem} ${100 - item.porcentagem}`;
                                                const offset = 100 - ac;
                                                ac += item.porcentagem;

                                                const coresSet = ["#414833", "#737A5D", "#A4AC86", "#CCBFA3", "#3a412e"];
                                                const cor = coresSet[idx % coresSet.length];

                                                return (
                                                    <circle
                                                        key={item.categoria}
                                                        cx="21"
                                                        cy="21"
                                                        r="15.915"
                                                        fill="none"
                                                        stroke={cor}
                                                        strokeWidth="5.5"
                                                        strokeDasharray={array}
                                                        strokeDashoffset={offset}
                                                        className="transition-all"
                                                    />
                                                );
                                            });
                                        })()}
                                    </svg>
                                    <div className="absolute w-20 h-20 rounded-full bg-white shadow-xs flex flex-col items-center justify-center">
                                        <span className="text-xl font-bold text-slate-800 font-mono">100%</span>
                                        <span className="text-[9px] text-[#737A5D] uppercase font-bold">Modas</span>
                                    </div>
                                </div>
                            </div>

                            <div className="space-y-1.5 mt-2">
                                {dadosGrafico.slice(0, 4).map((item, idx) => {
                                    const classesPaleta = ["bg-ebony", "bg-reseda", "bg-sage", "bg-dun", "bg-zinc-400"];
                                    return (
                                        <div key={item.categoria} className="flex items-center justify-between text-xs font-semibold">
                                            <div className="flex items-center gap-2 text-slate-600">
                                                <span className={`w-2.5 h-2.5 rounded-full ${classesPaleta[idx % classesPaleta.length]}`} />
                                                <span>{item.categoria}</span>
                                            </div>
                                            <span className="text-slate-800 font-mono">{item.porcentagem.toFixed(1)}%</span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                    <TriggerIcons />
                </div>
            );
        }

        // --- INVENTORY ---
        function Inventory({ produtos, onAdicionar, onAtualizar, onExcluir, podeEditar, onShowToast }) {
            const [busca, setBusca] = useState('');
            const [categoriaAtiva, setCategoriaAtiva] = useState('Todas');
            const [mostrarModal, setMostrarModal] = useState(false);
            const [editandoId, setEditandoId] = useState(null);

            // Campos do Formulário
            const [formNome, setFormNome] = useState('');
            const [formPreco, setFormPreco] = useState('');
            const [formEstoque, setFormEstoque] = useState('');
            const [formCategoria, setFormCategoria] = useState('Camisas');
            const [formTamanho, setFormTamanho] = useState('M');
            const [formImagemUrl, setFormImagemUrl] = useState('');

            // Dialog de Exclusão
            const [produtoExclusao, setProdutoExclusao] = useState(null);

            const categorias = ["Todas", ...Array.from(new Set(produtos.map(p => p.categoria || "Geral")))];

            const produtosFiltrados = produtos.filter(p => {
                const bateBusca = p.nome.toLowerCase().includes(busca.toLowerCase());
                const bateCategoria = categoriaAtiva === 'Todas' || p.categoria === categoriaAtiva;
                return bateBusca && bateCategoria;
            });

            const abrirNovoModal = () => {
                setEditandoId(null);
                setFormNome('');
                setFormPreco('');
                setFormEstoque('');
                setFormCategoria('Camisas');
                setFormTamanho('M');
                setFormImagemUrl(TEMPLATE_IMAGENS[0].url);
                setMostrarModal(true);
            };

            const abrirEditarModal = (prod) => {
                setEditandoId(prod.id);
                setFormNome(prod.nome);
                setFormPreco(prod.preco.toString());
                setFormEstoque(prod.estoque.toString());
                setFormCategoria(prod.categoria || 'Geral');
                setFormTamanho(prod.tamanho || 'M');
                setFormImagemUrl(prod.imagemUrl);
                setMostrarModal(true);
            };

            const lidarComSalvar = (e) => {
                e.preventDefault();
                if (!formNome.trim() || !formPreco || !formEstoque) {
                    onShowToast("Por favor preencha todos os campos obrigatórios.", "erro");
                    return;
                }

                const precoNum = parseFloat(formPreco.replace(',', '.'));
                const estoqueNum = parseInt(formEstoque);

                if (isNaN(precoNum) || precoNum < 0 || isNaN(estoqueNum) || estoqueNum < 0) {
                    onShowToast("Números inválidos inseridos nos campos.", "erro");
                    return;
                }

                const payload = {
                    nome: formNome.trim(),
                    preco: precoNum,
                    estoque: estoqueNum,
                    categoria: formCategoria,
                    tamanho: formTamanho,
                    imagemUrl: formImagemUrl.trim()
                };

                if (editandoId !== null) {
                    onAtualizar(editandoId, payload);
                } else {
                    onAdicionar(payload);
                }
                setMostrarModal(false);
            };

            return (
                <div className="space-y-8 animate-fade-in text-left">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Estoque de Roupas</h1>
                            <p className="text-slate-500 text-sm mt-1">Gerencie seu vestuário comercial premium e catálogos cadastrados.</p>
                        </div>
                        {podeEditar && (
                            <button
                                onClick={abrirNovoModal}
                                className="flex items-center justify-center gap-2 bg-ebony hover:bg-reseda text-white font-bold px-5 py-3 rounded-xl transition text-xs tracking-wider uppercase cursor-pointer"
                            >
                                <i data-lucide="plus-circle" className="w-4 h-4"></i>
                                <span>Novo Produto</span>
                            </button>
                        )}
                    </div>

                    <div className="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl border border-slate-150">
                        <div className="relative w-full md:max-w-md">
                            <i data-lucide="search" className="absolute left-3 top-3 text-slate-400 w-4 h-4"></i>
                            <input
                                type="text"
                                placeholder="Buscar peça..."
                                value={busca}
                                onChange={(e) => setBusca(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-200 rounded-lg pl-10 pr-4 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                            />
                        </div>

                        <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto py-1 scrollbar-none">
                            {categorias.map(cat => (
                                <button
                                    key={cat}
                                    onClick={() => setCategoriaAtiva(cat)}
                                    className={`px-3 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
                                        categoriaAtiva === cat ? 'bg-reseda text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                                    }`}
                                >
                                    {cat}
                                </button>
                            ))}
                        </div>
                    </div>

                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {produtosFiltrados.map((prod) => (
                            <div key={prod.id} className="bg-white rounded-xl overflow-hidden border border-slate-150 shadow-xs flex flex-col justify-between">
                                <div className="relative group bg-slate-100 aspect-square overflow-hidden">
                                    <img 
                                        src={prod.imagemUrl} 
                                        alt={prod.nome}
                                        className="w-full h-full object-cover transition duration-500 group-hover:scale-105"
                                        onError={(e) => { e.target.src = "https://images.unsplash.com/photo-1595777457583-95e059d581b8?w=600"; }}
                                    />
                                    <span className="absolute top-3 left-3 bg-white/95 text-slate-800 text-[10px] font-bold px-2.5 py-1 rounded-md shadow-xs uppercase tracking-wider">
                                        {prod.categoria}
                                    </span>
                                    {prod.estoque <= 0 ? (
                                        <div className="absolute inset-0 bg-black/40 flex items-center justify-center">
                                            <span className="bg-red-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg uppercase tracking-wider">Sem Estoque</span>
                                        </div>
                                    ) : prod.estoque < 10 ? (
                                        <span className="absolute top-3 right-3 bg-amber-500 text-white text-[9px] font-bold px-2 py-0.5 rounded-md uppercase tracking-wider animate-pulse">
                                            Baixo
                                        </span>
                                    ) : null}
                                </div>

                                <div className="p-5 flex-1 flex flex-col justify-between text-left">
                                    <div className="space-y-1">
                                        <div className="flex items-start justify-between gap-2">
                                            <h4 className="font-semibold text-slate-800 text-sm line-clamp-2 leading-snug">{prod.nome}</h4>
                                            <span className="bg-slate-100 text-slate-600 text-[11px] font-bold px-2 py-0.5 rounded font-mono shrink-0">
                                                {prod.tamanho}
                                            </span>
                                        </div>
                                        <p className="text-xs text-slate-400 font-medium">Cód. Identificador: #{prod.id}</p>
                                    </div>

                                    <div className="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                                        <div>
                                            <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Preço Unitário</p>
                                            <p className="text-base font-bold text-slate-800 font-mono mt-0.5">
                                                R$ {prod.preco.toFixed(2)}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-[10px] uppercase font-bold text-slate-400 tracking-wider">Disponível</p>
                                            <p className={`text-xs font-bold mt-1 ${prod.estoque < 10 ? 'text-amber-600' : 'text-slate-600'}`}>
                                                {prod.estoque} un
                                            </p>
                                        </div>
                                    </div>

                                    {podeEditar && (
                                        <div className="grid grid-cols-2 gap-2 mt-4 pt-2">
                                            <button 
                                                onClick={() => abrirEditarModal(prod)}
                                                className="flex items-center justify-center gap-1.5 border border-slate-200 hover:bg-slate-50 text-slate-600 py-2 rounded-lg text-xs font-semibold transition cursor-pointer"
                                            >
                                                <i data-lucide="edit-2" className="w-3.5 h-3.5"></i>
                                                <span>Editar</span>
                                            </button>
                                            <button 
                                                onClick={() => setProdutoExclusao(prod)}
                                                className="flex items-center justify-center gap-1.5 border border-red-200/60 hover:bg-red-50 text-red-600 py-2 rounded-lg text-xs font-semibold transition cursor-pointer"
                                            >
                                                <i data-lucide="trash-2" className="w-3.5 h-3.5"></i>
                                                <span>Excluir</span>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Form Modal */}
                    {mostrarModal && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4 overflow-y-auto">
                            <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl p-6 border border-slate-100 animate-scale-up text-left">
                                <div className="flex items-center justify-between border-b pb-4 mb-4">
                                    <h3 className="text-lg font-serif font-bold text-slate-800">
                                        {editandoId ? "Editar Produto Existente" : "Cadastrar Novo Vestuário"}
                                    </h3>
                                    <button onClick={() => setMostrarModal(false)} className="text-slate-400 hover:text-slate-600 cursor-pointer">
                                        <i data-lucide="x" className="w-5 h-5"></i>
                                    </button>
                                </div>

                                <form onSubmit={lidarComSalvar} className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Nome Comercial do Produto *</label>
                                        <input 
                                            type="text" 
                                            value={formNome} 
                                            onChange={(e) => setFormNome(e.target.value)}
                                            placeholder="Ex: Vestido Festa Seda Puro" 
                                            className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                        />
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Preço Venda (R$) *</label>
                                            <input 
                                                type="text" 
                                                value={formPreco} 
                                                onChange={(e) => setFormPreco(e.target.value)}
                                                placeholder="0.00" 
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-ebony"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Quantidade Estoque *</label>
                                            <input 
                                                type="number" 
                                                value={formEstoque} 
                                                onChange={(e) => setFormEstoque(e.target.value)}
                                                placeholder="10" 
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Categoria de Moda</label>
                                            <select 
                                                value={formCategoria} 
                                                onChange={(e) => setFormCategoria(e.target.value)}
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                            >
                                                <option value="Camisas">Camisas</option>
                                                <option value="Calças">Calças</option>
                                                <option value="Vestidos">Vestidos</option>
                                                <option value="Casacos">Casacos</option>
                                                <option value="Calçados">Calçados</option>
                                                <option value="Saias">Saias</option>
                                                <option value="Acessórios">Acessórios</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Tamanho / Grade</label>
                                            <input 
                                                type="text" 
                                                value={formTamanho} 
                                                onChange={(e) => setFormTamanho(e.target.value)}
                                                placeholder="M, 42, Único" 
                                                className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                            />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold uppercase text-slate-500 mb-1">Banco de Imagem Disponível (Escolha Rápida)</label>
                                        <div className="grid grid-cols-4 gap-2 border p-2 rounded-xl bg-slate-50 max-h-32 overflow-y-auto scrollbar-none">
                                            {TEMPLATE_IMAGENS.map(img => (
                                                <button
                                                    key={img.url}
                                                    type="button"
                                                    onClick={() => setFormImagemUrl(img.url)}
                                                    className={`relative aspect-square rounded-lg overflow-hidden border-2 transition ${
                                                        formImagemUrl === img.url ? 'border-ebony shadow-md scale-95' : 'border-transparent opacity-75 hover:opacity-100'
                                                    }`}
                                                >
                                                    <img src={img.url} className="w-full h-full object-cover" alt="" />
                                                </button>
                                            ))}
                                        </div>
                                    </div>

                                    <div className="pt-4 border-t flex justify-end gap-3">
                                        <button 
                                            type="button" 
                                            onClick={() => setMostrarModal(false)}
                                            className="px-4 py-2 border rounded-xl text-slate-500 hover:bg-slate-50 text-xs font-semibold uppercase tracking-wider cursor-pointer"
                                        >
                                            Cancelar
                                        </button>
                                        <button 
                                            type="submit"
                                            className="px-5 py-2 bg-ebony hover:bg-reseda text-white rounded-xl text-xs font-semibold uppercase tracking-wider cursor-pointer"
                                        >
                                            Confirmar e Salvar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* Delete Alert Modal */}
                    {produtoExclusao && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-xs p-4">
                            <div className="bg-white rounded-2xl max-w-sm w-full shadow-2xl p-6 border border-slate-100 animate-scale-up text-left">
                                <div className="flex items-center gap-3 text-red-600 mb-3">
                                    <i data-lucide="alert-triangle" className="w-6 h-6 shrink-0"></i>
                                    <h3 className="text-base font-bold font-serif">Excluir Produto Permanente?</h3>
                                </div>
                                <p className="text-slate-500 text-xs leading-relaxed">
                                    Você tem certeza que deseja remover o produto <strong className="text-slate-800">"{produtoExclusao.nome}"</strong>? Esta ação não pode ser revertida e limpará o registro local.
                                </p>
                                <div className="mt-5 flex justify-end gap-2.5">
                                    <button 
                                        onClick={() => setProdutoExclusao(null)}
                                        className="px-3.5 py-2 border rounded-xl text-slate-500 hover:bg-slate-50 text-xs font-semibold cursor-pointer"
                                    >
                                        Cancelar
                                    </button>
                                    <button 
                                        onClick={() => {
                                            onExcluir(produtoExclusao.id);
                                            setProdutoExclusao(null);
                                        }}
                                        className="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold cursor-pointer"
                                    >
                                        Sim, Excluir
                                    </button>
                                </div>
                            </div>
                        </div>
                    )}
                    <TriggerIcons />
                </div>
            );
        }

        // --- SALES HUB (PDV) ---
        function SalesHub({ produtos, clientes, onNovaVenda, onShowToast }) {
            const [carrinho, setCarrinho] = useState([]);
            const [clienteId, setClienteId] = useState(1); // Padrão: Consumidor Final
            const [descontoTexto, setDescontoTexto] = useState('');
            const [buscaFiltro, setBuscaFiltro] = useState('');

            const produtosDisponiveis = produtos.filter(p => p.nome.toLowerCase().includes(buscaFiltro.toLowerCase()));

            const totalBruto = carrinho.reduce((sum, item) => sum + (item.preco * item.quantidade), 0);
            const descontoNum = parseFloat(descontoTexto.replace(',', '.')) || 0;
            const totalLiquido = Math.max(0, totalBruto - descontoNum);

            const adicionarAoCarrinho = (prod) => {
                if (prod.estoque <= 0) {
                    onShowToast("Produto indisponível no estoque.", "erro");
                    return;
                }
                const existente = carrinho.find(item => item.id === prod.id);
                if (existente) {
                    if (existente.quantidade >= prod.estoque) {
                        onShowToast(`Quantidade máxima atingida para o estoque atual (${prod.estoque} un).`, "erro");
                        return;
                    }
                    setCarrinho(carrinho.map(item => item.id === prod.id ? { ...item, quantidade: item.quantidade + 1 } : item));
                } else {
                    setCarrinho([...carrinho, { ...prod, quantidade: 1 }]);
                }
            };

            const alterarQuantidade = (id, n) => {
                const item = carrinho.find(i => i.id === id);
                const prodOriginal = produtos.find(p => p.id === id);
                if (!item || !prodOriginal) return;

                const novaQtde = item.quantidade + n;
                if (novaQtde <= 0) {
                    setCarrinho(carrinho.filter(i => i.id !== id));
                } else {
                    if (novaQtde > prodOriginal.estoque) {
                        onShowToast(`Apenas ${prodOriginal.estoque} unidades disponíveis no estoque.`, "erro");
                        return;
                    }
                    setCarrinho(carrinho.map(i => i.id === id ? { ...i, quantidade: novaQtde } : i));
                }
            };

            const finalizarVenda = () => {
                if (carrinho.length === 0) {
                    onShowToast("Seu carrinho comercial está vazio.", "erro");
                    return;
                }
                if (descontoNum < 0 || descontoNum > totalBruto) {
                    onShowToast("Desconto inserido inválido ou maior que o total.", "erro");
                    return;
                }

                const objCliente = clientes.find(c => c.id === parseInt(clienteId));
                
                const itensVenda = carrinho.map(item => ({
                    idProduto: item.id,
                    nomeProduto: item.nome,
                    quantidade: item.quantidade,
                    precoUnitario: item.preco
                }));

                onNovaVenda({
                    clienteNome: objCliente?.nome || "Consumidor Final",
                    total: totalLiquido,
                    desconto: descontoNum,
                    itens: itensVenda
                });

                setCarrinho([]);
                setDescontoTexto('');
                setClienteId(1);
            };

            return (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-fade-in text-left">
                    <div className="lg:col-span-2 space-y-6">
                        <div>
                            <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Balcão de Vendas</h1>
                            <p className="text-slate-500 text-sm mt-1">Selecione roupas abaixo para registrar um novo pedido.</p>
                        </div>

                        <div className="relative w-full">
                            <i data-lucide="search" className="absolute left-3 top-3 text-slate-400 w-4 h-4"></i>
                            <input
                                type="text"
                                placeholder="Filtrar por nome do produto..."
                                value={buscaFiltro}
                                onChange={(e) => setBuscaFiltro(e.target.value)}
                                className="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-ebony shadow-xs"
                            />
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 max-h-[60vh] overflow-y-auto pr-1 scrollbar-none">
                            {produtosDisponiveis.map(p => (
                                <div 
                                    key={p.id} 
                                    onClick={() => adicionarAoCarrinho(p)}
                                    className={`bg-white border rounded-xl p-3 flex flex-col justify-between cursor-pointer transition select-none text-left ${
                                        p.estoque <= 0 ? 'opacity-50 border-slate-200 bg-slate-50' : 'hover:border-reseda shadow-xs hover:shadow-md'
                                    }`}
                                >
                                    <div className="flex gap-3 items-start">
                                        <img src={p.imagemUrl} className="w-12 h-12 object-cover rounded-md border shrink-0 bg-slate-50" alt="" />
                                        <div>
                                            <h4 className="font-semibold text-xs text-slate-800 line-clamp-2 leading-tight">{p.nome}</h4>
                                            <span className="inline-block mt-1 bg-slate-100 text-slate-600 font-mono text-[9px] px-1.5 py-0.5 rounded font-bold">{p.tamanho}</span>
                                        </div>
                                    </div>
                                    <div className="mt-3 pt-2 border-t flex items-center justify-between">
                                        <span className="font-mono text-xs font-bold text-slate-800">R$ {p.preco.toFixed(2)}</span>
                                        <span className={`text-[10px] font-semibold ${p.estoque < 10 ? 'text-amber-600':'text-slate-400'}`}>Estoque: {p.estoque} un</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* CHECKOUT SIDEBAR */}
                    <div className="bg-white rounded-2xl border border-slate-150 p-6 shadow-sm flex flex-col justify-between h-fit space-y-6">
                        <div className="space-y-4">
                            <div className="flex items-center justify-between border-b pb-3">
                                <h3 className="font-serif font-bold text-slate-800 text-base flex items-center gap-2">
                                    <i data-lucide="shopping-cart" className="w-4 h-4 text-ebony"></i>
                                    <span>Carrinho Atual</span>
                                </h3>
                                <span className="bg-bone text-ebony font-mono font-bold text-xs px-2.5 py-0.5 rounded-full">{carrinho.length} itens</span>
                            </div>

                            {/* Itens Lista */}
                            <div className="space-y-3 max-h-48 overflow-y-auto pr-1 scrollbar-none border-b pb-3">
                                {carrinho.length === 0 ? (
                                    <div className="text-center py-8 text-slate-400 text-xs font-medium">Nenhum item selecionado</div>
                                ) : (
                                    carrinho.map(item => (
                                        <div key={item.id} className="flex items-center justify-between text-xs gap-2">
                                            <div className="text-left flex-1 min-w-0">
                                                <p className="font-semibold text-slate-800 truncate">{item.nome}</p>
                                                <p className="text-slate-400 font-mono text-[10px]">R$ {item.preco.toFixed(2)} x {item.quantidade}</p>
                                            </div>
                                            <div className="flex items-center border rounded-lg overflow-hidden shrink-0 bg-slate-50">
                                                <button onClick={() => alterarQuantidade(item.id, -1)} className="px-2 py-1 hover:bg-slate-200 transition font-bold cursor-pointer">-</button>
                                                <span className="px-2 font-bold font-mono text-slate-700 bg-white">{item.quantidade}</span>
                                                <button onClick={() => alterarQuantidade(item.id, 1)} className="px-2 py-1 hover:bg-slate-200 transition font-bold cursor-pointer">+</button>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>

                            {/* Cliente / Descontos */}
                            <div className="space-y-3">
                                <div>
                                    <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Identificar Cliente</label>
                                    <select 
                                        value={clienteId}
                                        onChange={(e) => setClienteId(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-ebony"
                                    >
                                        {clientes.map(c => (
                                            <option key={c.id} value={c.id}>{c.nome} {c.cpf ? `(${c.cpf})`:''}</option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Conceder Desconto (R$)</label>
                                    <input 
                                        type="text" 
                                        placeholder="0,00"
                                        value={descontoTexto}
                                        onChange={(e) => setDescontoTexto(e.target.value)}
                                        className="w-full bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-ebony"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Totais do Checkout */}
                        <div className="pt-4 border-t space-y-2">
                            <div className="flex justify-between text-xs text-slate-500 font-medium font-mono">
                                <span>Subtotal Bruto:</span>
                                <span>R$ {totalBruto.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-xs text-amber-600 font-medium font-mono">
                                <span>Desconto:</span>
                                <span>- R$ {descontoNum.toFixed(2)}</span>
                            </div>
                            <div className="flex justify-between text-base font-bold text-slate-900 font-mono pt-1">
                                <span>Total Final:</span>
                                <span>R$ {totalLiquido.toFixed(2)}</span>
                            </div>

                            <button 
                                onClick={finalizarVenda}
                                className="w-full bg-ebony hover:bg-reseda text-white font-bold py-3 rounded-xl transition text-xs tracking-wider uppercase mt-4 cursor-pointer"
                            >
                                Emitir Pedido / Concluir
                            </button>
                        </div>
                    </div>
                    <TriggerIcons />
                </div>
            );
        }

        // --- CLIENTS MANAGER ---
        function ClientsManager({ clientes, onAdicionarCliente, onShowToast }) {
            const [nome, setNome] = useState('');
            const [telefone, setTelefone] = useState('');
            const [cpf, setCpf] = useState('');

            const lidarComCadastro = (e) => {
                e.preventDefault();
                if (!nome.trim()) {
                    onShowToast("O nome do cliente é obrigatório para o cadastro.", "erro");
                    return;
                }

                onAdicionarCliente({
                    nome: nome.trim(),
                    telefone: telefone.replace(/\D/g, ''),
                    cpf: cpf.replace(/\D/g, '')
                });

                setNome('');
                setTelefone('');
                setCpf('');
            };

            return (
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-8 animate-fade-in text-left">
                    <div className="lg:col-span-2 space-y-4">
                        <div>
                            <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Carteira de Clientes</h1>
                            <p className="text-slate-500 text-sm mt-1">Consulte e liste os perfis e históricos de contatos locais.</p>
                        </div>

                        <div className="bg-white border rounded-xl overflow-hidden shadow-xs">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50/70 border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                            <th className="px-6 py-3">ID</th>
                                            <th className="px-6 py-3">Nome Completo</th>
                                            <th className="px-6 py-3">Telefone</th>
                                            <th className="px-6 py-3">CPF</th>
                                        </tr>
                                    </thead>
                                    <tbody className="text-xs divide-y divide-slate-100 font-medium text-slate-700">
                                        {clientes.map(c => (
                                            <tr key={c.id} className="hover:bg-slate-50/50 transition">
                                                <td className="px-6 py-4 font-mono text-slate-400">#{c.id}</td>
                                                <td className="px-6 py-4 font-bold text-slate-900">{c.nome}</td>
                                                <td className="px-6 py-4 font-mono">{c.telefone || "Não Informado"}</td>
                                                <td className="px-6 py-4 font-mono">{c.cpf || "Não Informado"}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {/* CADASTRO FORM */}
                    <div className="bg-white border rounded-2xl p-6 shadow-xs h-fit space-y-4">
                        <div className="border-b pb-3">
                            <h3 className="font-serif font-bold text-slate-800 text-base">Novo Cadastro</h3>
                            <p className="text-slate-400 text-[11px]">Adicione um novo comprador ao banco de dados local.</p>
                        </div>

                        <form onSubmit={lidarComCadastro} className="space-y-4">
                            <div>
                                <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Nome Completo *</label>
                                <input 
                                    type="text" 
                                    placeholder="Ex: Clara Antunes"
                                    value={nome}
                                    onChange={(e) => setNome(e.target.value)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:ring-1 focus:ring-ebony"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Telefone Celular</label>
                                <input 
                                    type="text" 
                                    placeholder="Ex: 11999998888"
                                    value={telefone}
                                    onChange={(e) => setTelefone(e.target.value)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-ebony"
                                />
                            </div>
                            <div>
                                <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">CPF (Apenas números)</label>
                                <input 
                                    type="text" 
                                    placeholder="Ex: 00011122233"
                                    value={cpf}
                                    onChange={(e) => setCpf(e.target.value)}
                                    className="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-ebony"
                                />
                            </div>

                            <button 
                                type="submit"
                                className="w-full bg-ebony hover:bg-reseda text-white font-bold py-2.5 rounded-xl transition text-xs tracking-wider uppercase pt-2 cursor-pointer"
                            >
                                Cadastrar Cliente
                            </button>
                        </form>
                    </div>
                    <TriggerIcons />
                </div>
            );
        }

        // --- SALES HISTORY ---
        function SalesHistory({ vendas }) {
            return (
                <div className="space-y-6 animate-fade-in text-left">
                    <div>
                        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Histórico de Pedidos</h1>
                        <p className="text-slate-500 text-sm mt-1">Lista completa de faturamentos e notas comerciais emitidas.</p>
                    </div>

                    <div className="bg-white border rounded-xl overflow-hidden shadow-xs">
                        <div className="overflow-x-auto">
                            <table className="w-full text-left border-collapse">
                                <thead>
                                    <tr className="bg-slate-50/70 border-b border-slate-100 text-[10px] font-bold uppercase text-slate-400 tracking-wider">
                                        <th className="px-6 py-3">Código</th>
                                        <th className="px-6 py-3">Cliente</th>
                                        <th className="px-6 py-3">Itens Comprados</th>
                                        <th className="px-6 py-3">Desconto</th>
                                        <th className="px-6 py-3">Total Líquido</th>
                                        <th className="px-6 py-3">Data/Hora</th>
                                    </tr>
                                </thead>
                                <tbody className="text-xs divide-y divide-slate-100 font-medium text-slate-700">
                                    {vendas.length === 0 ? (
                                        <tr>
                                            <td colSpan="6" className="text-center py-8 text-slate-400">Nenhuma venda efetuada ainda.</td>
                                        </tr>
                                    ) : (
                                        vendas.map(v => (
                                            <tr key={v.id} className="hover:bg-slate-50/50 transition">
                                                <td className="px-6 py-4 font-mono text-slate-400">#{v.id}</td>
                                                <td className="px-6 py-4 font-bold text-slate-900">{v.clienteNome}</td>
                                                <td className="px-6 py-4">
                                                    <div className="space-y-0.5 max-w-xs">
                                                        {v.itens.map((it, idx) => (
                                                            <p key={idx} className="truncate text-slate-600">
                                                                <span className="font-bold text-slate-800 font-mono">{it.quantidade}x</span> {it.nomeProduto}
                                                            </p>
                                                        ))}
                                                    </div>
                                                </td>
                                                <td className="px-6 py-4 font-mono text-amber-600">R$ {v.desconto.toFixed(2)}</td>
                                                <td className="px-6 py-4 font-mono font-bold text-slate-900">R$ {v.total.toFixed(2)}</td>
                                                <td className="px-6 py-4 text-slate-400 font-mono">
                                                    {new Date(v.dataCriacao).toLocaleString('pt-BR')}
                                                </td>
                                            </tr>
                                        ))
                                    )}
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <TriggerIcons />
                </div>
            );
        }

        // --- INTERNAL TASKS ---
        function InternalTasks() {
            return (
                <div className="space-y-6 animate-fade-in text-left">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Tarefas</h1>
                            <p className="text-slate-500 text-sm mt-1">Acompanhamento operacional interno da equipe.</p>
                        </div>
                        <button className="flex items-center justify-center gap-2 bg-ebony hover:bg-reseda text-white font-bold px-4 py-2.5 rounded-xl transition text-xs tracking-wider uppercase cursor-pointer">
                            <i data-lucide="plus-circle" className="w-4 h-4"></i>
                            <span>Adicionar funcionário</span>
                        </button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white p-5 rounded-xl border border-slate-150 shadow-xs space-y-4">
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Pendente</span>
                                <span className="bg-slate-100 text-slate-600 px-2 py-0.5 rounded font-mono text-[10px]">2</span>
                            </h3>
                            <div className="space-y-3">
                                <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/60 space-y-2">
                                    <h4 className="font-semibold text-sm text-slate-800">Conferir NF de tecidos de linho</h4>
                                    <p className="text-xs text-slate-400">Verificar inconsistência de valores enviados pelo fornecedor.</p>
                                    <div className="flex justify-between items-center pt-2">
                                        <span className="bg-amber-100 text-amber-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase">Estoque</span>
                                        <span className="text-[10px] text-slate-400 font-mono">Alta</span>
                                    </div>
                                </div>
                                <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/60 space-y-2">
                                    <h4 className="font-semibold text-sm text-slate-800">Atualizar fotos do catálogo Outono</h4>
                                    <p className="text-xs text-slate-400">Trocar URLs expiradas no sistema por novas fotografias profissionais.</p>
                                    <div className="flex justify-between items-center pt-2">
                                        <span className="bg-blue-100 text-blue-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase">Geral</span>
                                        <span className="text-[10px] text-slate-400 font-mono">Média</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-5 rounded-xl border border-slate-150 shadow-xs space-y-4">
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Em Andamento</span>
                                <span className="bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-mono text-[10px]">1</span>
                            </h3>
                            <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/60 space-y-2">
                                <h4 className="font-semibold text-sm text-slate-800">Balanço do faturamento mensal</h4>
                                <p className="text-xs text-slate-400">Gerar planilha unificada com as vendas registradas via servidor local.</p>
                                <div className="flex justify-between items-center pt-2">
                                    <span className="bg-purple-100 text-purple-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase">Gerência</span>
                                    <span className="text-[10px] text-slate-400 font-mono">Crítica</span>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white p-5 rounded-xl border border-slate-150 shadow-xs space-y-4">
                            <h3 className="font-bold text-xs uppercase tracking-wider text-slate-400 flex items-center justify-between">
                                <span>Concluído</span>
                                <span className="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded font-mono text-[10px]">1</span>
                            </h3>
                            <div className="p-4 bg-slate-50/70 rounded-xl border border-slate-200/30 space-y-2 line-through opacity-60">
                                <h4 className="font-semibold text-sm text-slate-600">Cadastrar novos Oxford de Couro</h4>
                                <p className="text-xs text-slate-400">Inclusão concluída com 8 unidades em estoque inicial.</p>
                                <div className="flex justify-between items-center pt-2">
                                    <span className="bg-emerald-100 text-emerald-700 font-bold px-2 py-0.5 rounded text-[9px] uppercase">Estoque</span>
                                    <span className="text-[10px] text-slate-400 font-mono">Finalizada</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <TriggerIcons />
                </div>
            );
        }

        // --- MAIN APP APP ---
        function App() {
            const [abaAtiva, setAbaAtiva] = useState('dashboard');
            const [usuarioAtivo, setUsuarioAtivo] = useState(null);
            
            // Estados Principais do Sistema
            const [produtos, setProdutos] = useState([]);
            const [clientes, setClientes] = useState([]);
            const [vendas, setVendas] = useState([]);

            // Estado de Login Formulário
            const [username, setUsername] = useState('');
            const [password, setPassword] = useState('');

            // Global Toast
            const [toast, setToast] = useState(null);

            const showToast = (texto, tipo = 'sucesso') => {
                setToast({ texto, tipo });
            };

            // Carregamento de dados inicial
            useEffect(() => {
                fetch("/api/state")
                    .then(res => res.json())
                    .then(data => {
                        setProdutos(data.produtos || []);
                        setClientes(data.clientes || []);
                        setVendas(data.vendas || []);
                    })
                    .catch(err => {
                        console.error("Erro ao sincronizar com backend python:", err);
                        showToast("Não foi possível carregar a persistência local do JSON.", "erro");
                    });
            }, []);

            // Função Central de Sincronização e Envio POST
            const sincronizarComServidor = (novosProdutos, novosClientes, novasVendas) => {
                const payload = {
                    produtos: novosProdutos,
                    clientes: novosClientes,
                    vendas: novasVendas
                };

                fetch("/api/state", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payload)
                })
                .then(res => {
                    if(!res.ok) throw new Error("Erro na resposta do HTTP");
                    return res.json();
                })
                .then(data => {
                    if (data.status === "success") {
                        console.log("Banco de dados JSON local sincronizado com sucesso.");
                    } else {
                        showToast("Erro retornado ao salvar os arquivos locais.", "erro");
                    }
                })
                .catch(err => {
                    console.error(err);
                    showToast("Falha de conexão física com o servidor Python.", "erro");
                });
            };

            // Operações de Negócios
            const handleAdicionarProduto = (payload) => {
                const novoId = produtos.length > 0 ? Math.max(...produtos.map(p => p.id)) + 1 : 1;
                const novoProd = { id: novoId, ...payload };
                const atualizado = [...produtos, novoProd];
                setProdutos(atualizado);
                sincronizarComServidor(atualizado, clientes, vendas);
                showToast(`Produto "${payload.nome}" cadastrado com sucesso.`);
            };

            const handleAtualizarProduto = (id, payload) => {
                const atualizado = produtos.map(p => p.id === id ? { ...p, ...payload } : p);
                setProdutos(atualizado);
                sincronizarComServidor(atualizado, clientes, vendas);
                showToast(`Registro #${id} atualizado corretamente.`);
            };

            const handleExcluirProduto = (id) => {
                const atualizado = produtos.filter(p => p.id !== id);
                setProdutos(atualizado);
                sincronizarComServidor(atualizado, clientes, vendas);
                showToast(`Produto removido de catálogo com sucesso.`);
            };

            const handleAdicionarCliente = (payload) => {
                const novoId = clientes.length > 0 ? Math.max(...clientes.map(c => c.id)) + 1 : 1;
                const novoCliente = { id: novoId, ...payload };
                const atualizado = [...clientes, novoCliente];
                setClientes(atualizado);
                sincronizarComServidor(produtos, atualizado, vendas);
                showToast(`Cliente "${payload.nome}" registrado.`);
            };

            const handleNovaVenda = (payload) => {
                // Deduzir quantidades do estoque local
                const copiaProdutos = [...produtos];
                let estoqueValido = true;

                payload.itens.forEach(item => {
                    const prod = copiaProdutos.find(p => p.id === item.idProduto);
                    if (prod) {
                        if (prod.estoque < item.quantidade) {
                            estoqueValido = false;
                        } else {
                            prod.estoque -= item.quantidade;
                        }
                    }
                });

                if (!estoqueValido) {
                    showToast("A operação quebrou o limite disponível de estoque e foi abortada.", "erro");
                    return;
                }

                const novaId = vendas.length > 0 ? Math.max(...vendas.map(v => v.id)) + 1 : 1;
                const novaVenda = {
                    id: novaId,
                    dataCriacao: new Date().toISOString(),
                    ...payload
                };

                const listaVendasAtualizada = [...vendas, novaVenda];
                setProdutos(copiaProdutos);
                setVendas(listaVendasAtualizada);
                sincronizarComServidor(copiaProdutos, clientes, listaVendasAtualizada);
                showToast(`Pedido comercial #${novaId} finalizado e emitido!`);
                setAbaAtiva('vendas-historico');
            };

            // Sistema Autenticação Simples
            const efetuarLogin = (e) => {
                e.preventDefault();
                const usuarioEncontrado = USERS.find(u => u.usuario === username.trim().toLowerCase());
                
                if (usuarioEncontrado && password === "") {
                    setUsuarioAtivo(usuarioEncontrado);
                    showToast(`Bem-vindo de volta.`);
                } else {
                    showToast("Nome de usuário ou credenciais incorretas.", "erro");
                }
            };

            // Permissões ACL
            const podeEditarEstoque = usuarioAtivo?.cargo === 'gerente' || usuarioAtivo?.cargo === 'estoquista';

            if (!usuarioAtivo) {
                return (
                    <div className="min-h-screen flex items-center justify-center p-4 bg-[#FAF9F6] font-sans">
                        <div className="bg-white rounded-2xl border border-slate-150 p-8 shadow-xl max-w-sm w-full space-y-6 animate-scale-up text-center">
                            <div className="space-y-1.5">
                                <h2 className="text-2xl font-serif font-bold text-slate-900 tracking-tight">Acesso ao Painel</h2>
                                <p className="text-xs text-slate-400">Por favor, insira as credenciais básicas de acesso.</p>
                            </div>

                            <form onSubmit={efetuarLogin} className="space-y-4 text-left">
                                <div>
                                    <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Nome de Usuário</label>
                                    <div className="relative">
                                        <i data-lucide="user" className="absolute left-3 top-2.5 text-slate-400 w-4 h-4"></i>
                                        <input 
                                            type="text" 
                                            placeholder="" 
                                            value={username}
                                            onChange={(e) => setUsername(e.target.value)}
                                            className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label className="block text-[10px] font-bold uppercase text-slate-400 mb-1">Código de Segurança</label>
                                    <div className="relative">
                                        <i data-lucide="lock" className="absolute left-3 top-2.5 text-slate-400 w-4 h-4"></i>
                                        <input 
                                            type="password" 
                                            placeholder="" 
                                            value={password}
                                            onChange={(e) => setPassword(e.target.value)}
                                            className="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony"
                                        />
                                    </div>
                                </div>

                                <button 
                                    type="submit"
                                    className="w-full bg-ebony hover:bg-reseda text-white font-bold py-3 rounded-xl transition text-xs tracking-wider uppercase pt-2.5 cursor-pointer"
                                >
                                    Autenticar Sistema
                                </button>
                            </form>
                            <TriggerIcons />
                            {toast && <Toast texto={toast.texto} tipo={toast.tipo} onClose={() => setToast(null)} />}
                        </div>
                    </div>
                );
            }

            return (
                <div className="min-h-screen flex bg-[#FAF9F6] font-sans antialiased text-slate-600">
                    {/* SIDEBAR NAVIGATION */}
                    <aside className="w-64 bg-stone-900 shrink-0 text-stone-300 hidden md:flex flex-col justify-between border-r border-stone-850">
                        <div className="p-6 space-y-8 text-left">
                            <div className="border-b border-stone-800 pb-4">
                                <h2 className="font-serif font-bold text-white text-lg tracking-wide uppercase">Sistema</h2>
                                <p className="text-[10px] text-stone-500 font-mono tracking-wider mt-0.5">CONSOLE OPERACIONAL</p>
                            </div>

                            <nav className="space-y-1">
                                <button 
                                    onClick={() => setAbaAtiva('dashboard')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'dashboard' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="layout-dashboard" className="w-4 h-4"></i>
                                    <span>Painel Geral</span>
                                </button>
                                <button 
                                    onClick={() => setAbaAtiva('balcao')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'balcao' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="computer" className="w-4 h-4"></i>
                                    <span>Balcão PDV</span>
                                </button>
                                <button 
                                    onClick={() => setAbaAtiva('estoque')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'estoque' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="layers" className="w-4 h-4"></i>
                                    <span>Catálogo Estoque</span>
                                </button>
                                <button 
                                    onClick={() => setAbaAtiva('clientes')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'clientes' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="users" className="w-4 h-4"></i>
                                    <span>Clientes</span>
                                </button>
                                <button 
                                    onClick={() => setAbaAtiva('vendas-historico')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'vendas-historico' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="file-text" className="w-4 h-4"></i>
                                    <span>Histórico Vendas</span>
                                </button>
                                <button 
                                    onClick={() => setAbaAtiva('tarefas')}
                                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-xs font-bold uppercase tracking-wider transition cursor-pointer ${
                                        abaAtiva === 'tarefas' ? 'bg-[#414833] text-white' : 'hover:bg-stone-850 hover:text-white'
                                    }`}
                                >
                                    <i data-lucide="check-square" className="w-4 h-4"></i>
                                    <span>Tarefas</span>
                                </button>
                            </nav>
                        </div>

                        {/* USUÁRIO BOX FOOTER */}
                        <div className="p-4 border-t border-stone-800 bg-stone-950/30 flex items-center justify-between gap-2 text-left">
                            <div className="min-w-0">
                                <p className="text-xs font-bold text-white truncate uppercase tracking-wide">{usuarioAtivo.usuario}</p>
                                <p className="text-[10px] text-stone-500 uppercase font-mono tracking-wider mt-0.5">{usuarioAtivo.cargo}</p>
                            </div>
                            <button 
                                onClick={() => setUsuarioAtivo(null)}
                                className="p-2 text-stone-500 hover:text-red-400 rounded-lg hover:bg-stone-850 transition cursor-pointer"
                                title="Encerrar Sessão"
                            >
                                <i data-lucide="log-out" className="w-4 h-4"></i>
                            </button>
                        </div>
                    </aside>

                    {/* MAIN WORKING FRAME */}
                    <main className="flex-1 flex flex-col min-w-0 overflow-y-auto h-screen p-6 md:p-10">
                        {abaAtiva === 'dashboard' && <Dashboard produtos={produtos} vendas={vendas} />}
                        {abaAtiva === 'balcao' && <SalesHub produtos={produtos} clientes={clientes} onNovaVenda={handleNovaVenda} onShowToast={showToast} />}
                        {abaAtiva === 'estoque' && <Inventory produtos={produtos} onAdicionar={handleAdicionarProduto} onAtualizar={handleAtualizarProduto} onExcluir={handleExcluirProduto} podeEditar={podeEditarEstoque} onShowToast={showToast} />}
                        {abaAtiva === 'clientes' && <ClientsManager clientes={clientes} onAdicionarCliente={handleAdicionarCliente} onShowToast={showToast} />}
                        {abaAtiva === 'vendas-historico' && <SalesHistory vendas={vendas} />}
                        {abaAtiva === 'tarefas' && <InternalTasks />}
                    </main>

                    {/* RENDER TOAST GLOBALLY */}
                    {toast && <Toast texto={toast.texto} tipo={toast.tipo} onClose={() => setToast(null)} />}
                    <TriggerIcons />
                </div>
            );
        }

        const root = ReactDOM.createRoot(document.getElementById('root'));
        root.render(<App />);
    </script>
</body>
</html>
"""

def test_port(port):
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as s:
        return s.connect_ex(('localhost', port)) != 0

if __name__ == "__main__":
    while not test_port(PORT):
        print(f"Porta {PORT} ocupada, tentando {PORT + 1}...")
        PORT += 1

    handler = EleganciaRequestHandler
    with EleganciaTCPServer(("", PORT), handler) as httpd:
        print(f"==========================================================")
        print(f" SERVIDOR ATIVO EM: http://localhost:{PORT}")
        print(f" Banco de Dados Vinculado: {DATA_FILE}")
        print(f" Pressione CTRL+C para encerrar com segurança.")
        print(f"==========================================================")
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\nEncerrando servidor...")
            sys.exit(0)