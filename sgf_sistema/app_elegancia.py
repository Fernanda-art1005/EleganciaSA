import http.server
import socketserver
import json
import os
import sys

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
    def do_GET(self):
        # API de Carregamento de Estado do Sistema
        if self.path == "/api/state":
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.send_header("Access-Control-Allow-Origin", "*")
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
                sucesso = salvar_dados(dados_atualizados)
                
                self.send_response(200 if sucesso else 500)
                self.send_header("Content-Type", "application/json")
                self.send_header("Access-Control-Allow-Origin", "*")
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
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type")
        self.end_headers()

HTML_UI = """<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elegância Premium - Sistema Integrado</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- React & ReactDOM (UMD Production) -->
    <script src="https://unpkg.com/react@18/umd/react.production.min.js" crossorigin></script>
    <script src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js" crossorigin></script>
    <!-- Babel Standalone (Para JSX em tempo de execução) -->
    <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        serif: ['"Playfair Display"', 'ui-serif', 'Georgia', 'serif'],
                        sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
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
        body { background-color: #F9F8F6; }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useMemo } = React;

        // Script de imagens recentes editadas pelo usuário
        const TEMPLATE_IMAGENS = [
            { nome: "Saia Jeans", url: "https://misleading-indigo-rrfktsvo.edgeone.app/SAIA.jpg" },
            { nome: "Bolsa Azul Elegante", url: "https://decent-sapphire-pv9iwk9o.edgeone.app/bolsa.jpg" },
            { nome: "Oculos Elegante", url: "https://big-amber-aonkcumd.edgeone.app/oculos.jpg" },
            { nome: "Tenis Adidas Vermelho", url: "https://visible-white-bpzhicio.edgeone.app/tenis.jpg" },
            { nome: "Calça Jenas Preta", url: "https://devoted-salmon-wygt31hm.edgeone.app/calca.jpg" },
            { nome: "Vestido Off-White Curto", url: "https://limited-orange-cz77dz82.edgeone.app/vestido.jpg" },
            { nome: "Vestido Florido Branco e Azul", url: "https://incredible-lavender-aoqkgml2.edgeone.app/vestido2.jpg" },
            { nome: "Calça Moletom Cinza", url: "https://sound-bronze-og81vhsv.edgeone.app/cal\u00e7a%202.jpg" },
            { nome: "Salto Alto Preto", url: "https://wandering-scarlet-6oj4heo4.edgeone.app/salto.jpg" },
            { nome: "Casaco Couro Vermelho", url: "https://fundamental-bronze-dxejkhvn.edgeone.app/casaco2.jpg" },
            { nome: "Bolsa Azul 2", url: "https://uninterested-aquamarine-m4ergnn6.edgeone.app/bolsa2.jpg" },
            { nome: "Saia Branca", url: "https://numerous-apricot-pzyxj1tv.edgeone.app/saia%202.jpg" }
        ];

        const USERS = [
            { id: 1, usuario: "maria", nome: "Maria Silva (Gerente)", cargo: "gerente" },
            { id: 2, usuario: "carlos", nome: "Carlos Souza (Vendedor)", cargo: "vendedor" },
            { id: 3, usuario: "patricia", nome: "Patricia Lima (Estoque)", cargo: "estoquista" }
        ];

        // Helper para disparar render de ícones do lucide após o React atualizar o DOM
        const TriggerIcons = () => {
            useEffect(() => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
            return null;
        };

        // Componente de Toast/Notificação
        function Toast({ texto, tipo, onClose }) {
            useEffect(() => {
                const timer = setTimeout(onClose, 3500);
                return () => clearTimeout(timer);
            }, [onClose]);

            const isErro = tipo === 'erro';

            return (
                <div className={`fixed top-6 right-6 z-50 p-4 rounded-xl shadow-xl flex items-center gap-3 border transition-all transform duration-300 scale-100 ${
                    isErro ? 'bg-rose-950 border-rose-900/40 text-rose-200' : 'bg-stone-900 border-stone-850 text-white'
                }`}>
                    <i data-lucide={isErro ? "alert-triangle" : "check-circle"} className={isErro ? "text-rose-400 shrink-0" : "text-sage shrink-0"}></i>
                    <div className="text-left text-xs">
                        <p className={`font-bold uppercase tracking-wider ${isErro ? 'text-rose-400' : 'text-[#A4AC86]'}`}>
                            {isErro ? 'Atenção' : 'Operação Efetuada'}
                        </p>
                        <p className="text-stone-300 font-medium">{texto}</p>
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
                let totalFaturadoCategoriaCalculavel = 0;

                vendas.forEach(venda => {
                    venda.itens.forEach(item => {
                        const prod = produtos.find(p => p.id === item.idProduto);
                        const categoria = prod?.categoria || "Outros";
                        const valorItem = item.quantidade * item.precoUnitario;
                        vendasPorCategoria[categoria] = (vendasPorCategoria[categoria] || 0) + valorItem;
                        totalFaturadoCategoriaCalculavel += valorItem;
                    });
                });

                const categoriasValidas = Object.keys(vendasPorCategoria).length > 0;
                const mapaFrazione = categoriasValidas ? vendasPorCategoria : {
                    "Vestidos": 450.00,
                    "Calças": 199.90,
                    "Camisas": 159.90,
                    "Calçados": 320.00,
                    "Acessórios": 89.90
                };

                return Object.keys(mapaFrazione).map(cat => {
                    const valor = mapaFrazione[cat];
                    const totalDivisor = categoriasValidas ? totalFaturadoCategoriaCalculavel : (450 + 199.9 + 159.9 + 320 + 89.9);
                    const porcentagem = totalDivisor > 0 ? (valor / totalDivisor) * 100 : 0;
                    return { categoria: cat, valor, porcentagem };
                }).sort((a, b) => b.valor - a.valor);
            }, [vendas, produtos]);

            const getCorGargantilha = (cat, idx) => {
                const cores = {
                    "Vestidos": "from-ebony to-reseda",
                    "Calças": "from-[#525a41] to-ebony",
                    "Camisas": "from-[#858d6f] to-reseda",
                    "Calçados": "from-sage to-[#8f9772]",
                    "Acessórios": "from-dun to-[#b9ac90]",
                    "Casacos": "from-ebony to-[#353b2a]",
                    "Saias": "from-[#9ca47e] to-sage"
                };
                return cores[cat] || (idx % 2 === 0 ? "from-ebony to-reseda" : "from-sage to-dun");
            };

            return (
                <div className="space-y-8 animate-fade-in">
                    <div>
                        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Painel de Desempenho</h1>
                        <p className="text-slate-500 text-sm mt-1">Estatísticas consolidadas e análise proporcional de vendas.</p>
                    </div>

                    {/* Cards Superiores */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Faturamento</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1 font-mono">
                                    R$ {totalFaturado.toLocaleString('pt-BR', { minimumFractionDigits: 2 })}
                                </h3>
                                <span className="text-[11px] text-emerald-600 flex items-center gap-1 mt-1 font-medium">
                                    <i data-lucide="arrow-up-right" className="w-3.5 h-3.5"></i> +12% esta semana
                                </span>
                            </div>
                            <div className="p-3 bg-bone/40 text-ebony rounded-xl">
                                <i data-lucide="dollar-sign"></i>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Vendas</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1">{vendas.length} pedidos</h3>
                                <span className="text-[11px] text-emerald-600 flex items-center gap-1 mt-1 font-medium animate-pulse">
                                    Ticket Médio: R$ {(vendas.length > 0 ? totalFaturado / vendas.length : 0).toFixed(2)}
                                </span>
                            </div>
                            <div className="p-3 bg-bone/40 text-ebony rounded-xl">
                                <i data-lucide="shopping-bag"></i>
                            </div>
                        </div>

                        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex items-center justify-between">
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Peças Vendidas</span>
                                <h3 className="text-xl font-bold text-slate-800 mt-1 font-mono">{totalItensVendidos} un</h3>
                                <span className="text-[11px] text-slate-500 flex items-center gap-1 mt-1 font-medium">
                                    Moda premium & acessórios
                                </span>
                            </div>
                            <div className="p-3 bg-bone/40 text-ebony rounded-xl">
                                <i data-lucide="package"></i>
                            </div>
                        </div>

                        <div className={`p-6 rounded-xl shadow-sm border transition-all duration-300 ${estoqueBaixoCount > 0 ? 'bg-rose-50/70 border-rose-100' : 'bg-white border-slate-100'} flex items-center justify-between`}>
                            <div>
                                <span className="text-xs text-slate-400 font-bold tracking-wider uppercase">Alerta de Estoque</span>
                                <h3 className={`text-xl font-bold mt-1 ${estoqueBaixoCount > 0 ? 'text-rose-800' : 'text-slate-800'}`}>
                                    {estoqueBaixoCount} itens
                                </h3>
                                <span className="text-[11px] text-rose-600 flex items-center gap-1 mt-1 font-medium">
                                    {estoqueBaixoCount > 0 ? 'Abaixo do limite ideal (< 10 un)' : 'Estoque regularizado'}
                                </span>
                            </div>
                            <div className={`p-3 rounded-xl ${estoqueBaixoCount > 0 ? 'bg-rose-100 text-rose-700 animate-pulse' : 'bg-stone-105 text-stone-500'}`}>
                                <i data-lucide="alert-triangle"></i>
                            </div>
                        </div>
                    </div>

                    {/* Gráfico Linear de Distribuição de Vendas */}
                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 lg:col-span-2 flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 className="text-lg font-semibold text-slate-800 font-serif">Distribuição de Vendas (%)</h3>
                                        <p className="text-slate-400 text-xs">Faturamento proporcional por linha de vestuário.</p>
                                    </div>
                                    <div className="flex items-center gap-1.5 text-xs bg-bone/80 text-ebony font-semibold px-2.5 py-1 rounded-full border border-dun/30">
                                        <i data-lucide="trending-up" className="w-3 h-3"></i>
                                        <span>Moda Atualizada</span>
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    {dadosGrafico.map((item, idx) => (
                                        <div key={item.categoria} className="group">
                                            <div className="flex justify-between text-sm mb-1">
                                                <span className="text-slate-700 font-semibold">{item.categoria}</span>
                                                <div className="text-slate-500 text-xs font-mono">
                                                    <span className="text-slate-800 font-bold mr-2">{item.porcentagem.toFixed(1)}%</span>
                                                    <span>R$ {item.valor.toFixed(2)}</span>
                                                </div>
                                            </div>
                                            <div className="w-full bg-slate-50 rounded-full h-3 overflow-hidden border border-slate-100">
                                                <div 
                                                    style={{ width: `${item.porcentagem}%` }} 
                                                    className={`bg-gradient-to-r ${getCorGargantilha(item.categoria, idx)} h-full rounded-full transition-all duration-1000 ease-out`}
                                                />
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="mt-8 pt-4 border-t border-slate-100 flex justify-between items-center text-[10px] text-slate-400 font-semibold uppercase">
                                <span>Cálculos baseados em registros fiscais reais</span>
                                <span className="font-medium text-slate-600">Sincronizado</span>
                            </div>
                        </div>

                        {/* Donut SVG */}
                        <div className="bg-white p-6 rounded-xl shadow-sm border border-slate-100 flex flex-col justify-between">
                            <div>
                                <h3 className="text-lg font-semibold text-slate-800 font-serif">Participação de Coleção</h3>
                                <p className="text-slate-400 text-xs mb-6 font-medium">Proporção visual das categorias vendidas.</p>

                                <div className="relative flex justify-center items-center py-4">
                                    <svg width="150" height="150" viewBox="0 0 42 42" className="transform -rotate-90">
                                        <circle cx="21" cy="21" r="15.915" fill="transparent" stroke="#f1f5f9" strokeWidth="4.5" />
                                        {(() => {
                                            let acumulador = 0;
                                            return dadosGrafico.map((item, idx) => {
                                                const strokeDasharray = `${item.porcentagem} ${100 - item.porcentagem}`;
                                                const strokeDashoffset = 100 - acumulador;
                                                acumulador += item.porcentagem;

                                                const coresSet = ["#414833", "#737A5D", "#A4AC86", "#CCBFA3", "#EBE3D2"];
                                                const corCorrente = coresSet[idx % coresSet.length];

                                                return (
                                                    <circle
                                                        key={item.categoria}
                                                        cx="21"
                                                        cy="21"
                                                        r="15.915"
                                                        fill="transparent"
                                                        stroke={corCorrente}
                                                        strokeWidth="4.5"
                                                        strokeDasharray={strokeDasharray}
                                                        strokeDashoffset={strokeDashoffset}
                                                        className="transition-all duration-300"
                                                    />
                                                );
                                            });
                                        })()}
                                    </svg>
                                    <div className="absolute inset-x-0 inset-y-0 m-auto w-20 h-20 rounded-full bg-white shadow-inner flex flex-col items-center justify-center">
                                        <span className="text-2xl font-bold text-slate-800">100%</span>
                                        <span className="text-[9px] text-[#A4AC86] uppercase tracking-widest font-bold font-sans mt-0.5">Total</span>
                                    </div>
                                </div>
                            </div>
                            
                            <div className="space-y-1.5 mt-4">
                                {dadosGrafico.slice(0, 4).map((item, idx) => {
                                    const classesPaleta = ["bg-ebony", "bg-reseda", "bg-sage", "bg-dun", "bg-bone"];
                                    const corClasseComp = classesPaleta[idx % classesPaleta.length];
                                    return (
                                        <div key={item.categoria} className="flex items-center justify-between text-xs font-semibold">
                                            <div className="flex items-center gap-2 text-slate-600">
                                                <span className={`w-2.5 h-2.5 rounded-full ${corClasseComp}`} />
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
            const [modVisualizacao, setModVisualizacao] = useState('grade');
            const [mostrarModal, setMostrarModal] = useState(false);
            const [editandoId, setEditandoId] = useState(null);

            // Campos Form
            const [formNome, setFormNome] = useState('');
            const [formPreco, setFormPreco] = useState('');
            const [formEstoque, setFormEstoque] = useState('');
            const [formCategoria, setFormCategoria] = useState('Camisas');
            const [formTamanho, setFormTamanho] = useState('M');
            const [formImagemUrl, setFormImagemUrl] = useState('');

            // Delete dialog
            const [produtoExclusao, setProdutoExclusao] = useState(null);

            const categorias = ["Todas", ...Array.from(new Set(produtos.map(p => p.categoria || "Outros")))];

            const produtosFiltrados = produtos.filter(p => {
                const bateBusca = p.nome.toLowerCase().includes(busca.toLowerCase()) || 
                                  (p.categoria && p.categoria.toLowerCase().includes(busca.toLowerCase()));
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
                setFormCategoria(prod.categoria || 'Outros');
                setFormTamanho(prod.tamanho || 'M');
                setFormImagemUrl(prod.imagemUrl);
                setMostrarModal(true);
            };

            const lidarComSalvar = (e) => {
                e.preventDefault();
                if (!formNome.trim() || !formPreco || !formEstoque) {
                    onShowToast("Preencha todos os campos obrigatórios.", "erro");
                    return;
                }

                const precoNum = parseFloat(formPreco.toString().replace(',', '.'));
                const estoqueNum = parseInt(formEstoque.toString());

                if (isNaN(precoNum) || precoNum < 0 || isNaN(estoqueNum) || estoqueNum < 0) {
                    onShowToast("Preço ou estoque inválido.", "erro");
                    return;
                }

                const payload = {
                    nome: formNome.trim(),
                    preco: precoNum,
                    estoque: estoqueNum,
                    categoria: formCategoria,
                    tamanho: formTamanho,
                    imagemUrl: formImagemUrl.trim() || TEMPLATE_IMAGENS[0].url
                };

                if (editandoId !== null) {
                    onAtualizar(editandoId, payload);
                    onShowToast(`Peça "${payload.nome}" editada com sucesso!`, "sucesso");
                } else {
                    onAdicionar(payload);
                    onShowToast(`Nova peça "${payload.nome}" cadastrada!`, "sucesso");
                }
                setMostrarModal(false);
            };

            return (
                <div className="space-y-8 animate-fade-in">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Estoque de Produtos</h1>
                            <p className="text-slate-500 text-sm mt-1">Gerencie o seu catálogo de roupas finas e controle físico de estoque.</p>
                        </div>
                        {podeEditar && (
                            <button
                                onClick={abrirNovoModal}
                                className="flex items-center justify-center gap-2 bg-ebony hover:bg-reseda text-white font-semibold px-5 py-3 rounded-xl transition duration-200 text-xs tracking-wider uppercase cursor-pointer"
                            >
                                <i data-lucide="plus-circle" className="w-4 h-4"></i>
                                <span>Novo Produto</span>
                            </button>
                        )}
                    </div>

                    {/* Filtros */}
                    <div className="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
                        <div className="relative w-full md:max-w-md">
                            <i data-lucide="search" className="absolute left-3 top-3.5 text-slate-400 w-4 h-4"></i>
                            <input
                                type="text"
                                placeholder="Buscar peça, linha ou categoria..."
                                value={busca}
                                onChange={(e) => setBusca(e.target.value)}
                                className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-10 pr-4 py-2.5 text-sm focus:outline-none focus:ring-1 focus:ring-ebony focus:bg-white text-slate-800"
                            />
                        </div>

                        <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto py-1 scrollbar-none">
                            {categorias.map(cat => (
                                <button
                                    key={cat}
                                    onClick={() => setCategoriaAtiva(cat)}
                                    className={`px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition cursor-pointer ${
                                        categoriaAtiva === cat ? 'bg-reseda text-white' : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
                                    }`}
                                >
                                    {cat}
                                </button>
                            ))}
                        </div>

                        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
                            <button onClick={() => setModVisualizacao('grade')} className={`p-1.5 rounded-md cursor-pointer ${modVisualizacao === 'grade' ? 'bg-white text-reseda' : 'text-slate-400'}`}>
                                <i data-lucide="grid" className="w-4 h-4"></i>
                            </button>
                            <button onClick={() => setModVisualizacao('tabela')} className={`p-1.5 rounded-md cursor-pointer ${modVisualizacao === 'tabela' ? 'bg-white text-reseda' : 'text-slate-400'}`}>
                                <i data-lucide="list" className="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>

                    {/* Conteúdo do Catálogo */}
                    {produtosFiltrados.length === 0 ? (
                        <div className="bg-white rounded-xl border border-slate-100 text-center py-16 px-4">
                            <i data-lucide="alert-circle" className="mx-auto text-stone-300 mb-4 w-12 h-12"></i>
                            <h3 className="text-lg font-medium text-slate-700">Nenhum produto correspondente</h3>
                        </div>
                    ) : modVisualizacao === 'grade' ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                            {produtosFiltrados.map((prod) => (
                                <div key={prod.id} className="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-xs hover:shadow-md transition-all duration-300 flex flex-col justify-between group">
                                    <div className="relative h-64 bg-slate-50 overflow-hidden">
                                        <img src={prod.imagemUrl} alt={prod.nome} className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                        <div className="absolute top-3 left-3 bg-stone-900/80 backdrop-blur-xs text-white text-[10px] font-bold px-2 rounded uppercase">
                                            {prod.categoria || "Moda"}
                                        </div>
                                        <div className="absolute top-3 right-3 bg-white text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">
                                            TAM: {prod.tamanho || "M"}
                                        </div>
                                        {prod.estoque < 10 && (
                                            <div className="absolute bottom-3 left-3 flex items-center gap-1 bg-[#a64b2a]/95 text-white text-[10px] font-bold px-2.5 py-0.5 rounded shadow-xs">
                                                <i data-lucide="alert-triangle" className="w-3 h-3"></i>
                                                <span>Estoque Crítico: {prod.estoque} un</span>
                                            </div>
                                        )}
                                    </div>

                                    <div className="p-4 flex-1 flex flex-col justify-between space-y-3">
                                        <div>
                                            <h4 className="text-sm font-semibold text-slate-900 group-hover:text-reseda transition-colors truncate">{prod.nome}</h4>
                                            <p className="text-base font-bold text-slate-800 mt-1 font-mono">R$ {prod.preco.toFixed(2)}</p>
                                        </div>
                                        <div className="flex items-center justify-between pt-3 border-t border-slate-50 text-xs">
                                            <span className={`font-semibold ${prod.estoque >= 10 ? 'text-slate-500' : 'text-[#a64b2a]'}`}>
                                                Quantidade: {prod.estoque} un
                                            </span>
                                            {podeEditar && (
                                                <div className="flex items-center gap-1">
                                                    <button onClick={() => abrirEditarModal(prod)} className="p-1.5 bg-slate-50 hover:bg-slate-100 text-slate-500 hover:text-reseda rounded-lg transition" title="Editar">
                                                        <i data-lucide="edit-3" className="w-3.5 h-3.5"></i>
                                                    </button>
                                                    <button onClick={() => setProdutoExclusao(prod)} className="p-1.5 bg-slate-50 hover:bg-rose-50 text-slate-500 hover:text-red-600 rounded-lg transition" title="Excluir">
                                                        <i data-lucide="trash-2" className="w-3.5 h-3.5"></i>
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-sm">
                            <div className="overflow-x-auto">
                                <table className="w-full text-left text-xs border-collapse">
                                    <thead>
                                        <tr className="bg-slate-50 text-slate-400 font-bold uppercase tracking-wider border-b border-rose-50">
                                            <th className="px-6 py-4">Peça</th>
                                            <th className="px-6 py-4">Categoria</th>
                                            <th className="px-6 py-4">Tamanho</th>
                                            <th className="px-6 py-4 text-right">Preço</th>
                                            <th className="px-6 py-4 text-center">Unidades</th>
                                            <th className="px-6 py-4">Status</th>
                                            {podeEditar && <th className="px-6 py-4 text-right">Ações</th>}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-slate-150 text-sm text-slate-705 font-medium">
                                        {produtosFiltrados.map((prod) => (
                                            <tr key={prod.id} className="hover:bg-slate-50/50 transition">
                                                <td className="px-6 py-3 font-semibold text-slate-800">
                                                    <div className="flex items-center gap-3">
                                                        <img src={prod.imagemUrl} alt={prod.nome} className="w-10 h-10 object-cover rounded border border-slate-100" />
                                                        <span>{prod.nome}</span>
                                                    </div>
                                                </td>
                                                <td className="px-6 py-3">{prod.categoria || "Geral"}</td>
                                                <td className="px-6 py-3 font-mono">{prod.tamanho || "M"}</td>
                                                <td className="px-6 py-3 text-right font-bold font-mono">R$ {prod.preco.toFixed(2)}</td>
                                                <td className="px-6 py-3 text-center font-bold font-mono">{prod.estoque}</td>
                                                <td className="px-6 py-3">
                                                    <span className={`inline-flex items-center gap-1 font-bold ${prod.estoque >= 10 ? 'text-emerald-600' : 'text-[#a64b2a]'}`}>
                                                        <i data-lucide={prod.estoque >= 10 ? "check-circle" : "alert-triangle"} className="w-3.5 h-3.5"></i>
                                                        {prod.estoque >= 10 ? 'Regular' : 'Crítico'}
                                                    </span>
                                                </td>
                                                {podeEditar && (
                                                    <td className="px-6 py-3 text-right">
                                                        <div className="inline-flex gap-1">
                                                            <button onClick={() => abrirEditarModal(prod)} className="p-1.5 text-slate-500 hover:text-reseda bg-slate-50 hover:bg-slate-100 rounded-lg transition"><i data-lucide="edit-3" className="w-4 h-4"></i></button>
                                                            <button onClick={() => setProdutoExclusao(prod)} className="p-1.5 text-slate-500 hover:text-red-650 bg-slate-50 hover:bg-rose-50 rounded-lg transition"><i data-lucide="trash-2" className="w-4 h-4"></i></button>
                                                        </div>
                                                    </td>
                                                )}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* MODAL EDITAR / CADASTRAR PRODUTO */}
                    {mostrarModal && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onClick={() => setMostrarModal(false)} />
                            <div className="relative w-full max-w-md bg-white rounded-2xl shadow-xl p-6 space-y-5 animate-scale-up text-left z-10 text-slate-800">
                                <h3 className="text-xl font-serif text-slate-900 font-bold">
                                    {editandoId !== null ? "Editar Ficha de Peça" : "Nova Peça para Catálogo"}
                                </h3>
                                <form onSubmit={lidarComSalvar} className="space-y-4">
                                    <div>
                                        <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Nome *</label>
                                        <input type="text" required value={formNome} onChange={(e) => setFormNome(e.target.value)} className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony" />
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Preço (R$) *</label>
                                            <input type="text" required value={formPreco} onChange={(e) => setFormPreco(e.target.value)} className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony" />
                                        </div>
                                        <div>
                                            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Estoque Inicial *</label>
                                            <input type="number" required value={formEstoque} onChange={(e) => setFormEstoque(e.target.value)} className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-ebony" />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Categoria</label>
                                            <select value={formCategoria} onChange={(e) => setFormCategoria(e.target.value)} className="w-full bg-slate-50 border border-slate-150 rounded-lg px-3 py-2 text-sm focus:outline-none">
                                                <option value="Camisas">Camisas</option>
                                                <option value="Calças">Calças</option>
                                                <option value="Vestidos">Vestidos</option>
                                                <option value="Casacos">Casacos</option>
                                                <option value="Saias">Saias</option>
                                                <option value="Acessórios">Acessórios</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-[10px] font-bold text-slate-400 uppercase mb-1">Tamanho</label>
                                            <select value={formTamanho} onChange={(e) => setFormTamanho(e.target.value)} className="w-full bg-slate-50 border border-slate-150 rounded-lg px-3 py-2 text-sm focus:outline-none">
                                                <option value="P">P</option>
                                                <option value="M">M</option>
                                                <option value="G">G</option>
                                                <option value="GG">GG</option>
                                                <option value="Único">Tamanho Único</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-slate-400 text-left mb-1.5 uppercase">Presets de Imagem Recentes</label>
                                        <div className="grid grid-cols-6 gap-2 mb-2">
                                            {TEMPLATE_IMAGENS.map((preset, pIdx) => (
                                                <button key={pIdx} type="button" onClick={() => setFormImagemUrl(preset.url)} className={`relative h-10 w-full rounded-lg border overflow-hidden ${formImagemUrl === preset.url ? 'border-ebony ring-2 ring-ebony/30' : 'border-slate-100'}`}>
                                                    <img src={preset.url} alt={preset.nome} className="w-full h-full object-cover" />
                                                </button>
                                            ))}
                                        </div>
                                        <input type="url" value={formImagemUrl} onChange={(e) => setFormImagemUrl(e.target.value)} placeholder="Ou cole um link de imagem..." className="w-full bg-slate-50 border border-slate-150 rounded-lg px-3 py-1.5 text-xs text-slate-800" />
                                    </div>

                                    <div className="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-50">
                                        <button type="button" onClick={() => setMostrarModal(false)} className="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl font-semibold transition text-xs">Cancelar</button>
                                        <button type="submit" className="px-5 py-2.5 bg-ebony text-white rounded-xl font-bold uppercase tracking-wider text-xs shadow-md shadow-slate-100 cursor-pointer">Salvar Peça</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* CONFIRMAÇÃO EXCLUSÃO */}
                    {produtoExclusao && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onClick={() => setProdutoExclusao(null)} />
                            <div className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 space-y-4 text-center z-10 text-slate-800">
                                <div className="mx-auto w-12 h-12 bg-rose-50 text-[#a64b2a] rounded-full flex items-center justify-center">
                                    <i data-lucide="alert-triangle" className="w-6 h-6 animate-bounce"></i>
                                </div>
                                <h3 className="text-lg font-serif font-bold text-slate-950">Excluir Produto</h3>
                                <p className="text-slate-500 text-xs">Você realmente deseja remover permanentemente <b>"{produtoExclusao.nome}"</b> do catálogo?</p>
                                <div className="flex gap-2.5 pt-2">
                                    <button onClick={() => setProdutoExclusao(null)} className="w-full py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs">Retroceder</button>
                                    <button onClick={() => {
                                        onExcluir(produtoExclusao.id);
                                        onShowToast("Produto removido com sucesso!", "sucesso");
                                        setProdutoExclusao(null);
                                    }} className="w-full py-2.5 bg-[#a64b2a] text-white font-bold rounded-xl text-xs uppercase tracking-wider">Sim, Excluir</button>
                                </div>
                            </div>
                        </div>
                    )}
                    <TriggerIcons />
                </div>
            );
        }

        // --- PDV (CAIXA / VENDAS) ---
        function PDV({ produtos, clientes, onAdicionarCliente, onAtualizarCliente, onExcluirCliente, onConfirmarVenda, onShowToast }) {
            const [carrinho, setCarrinho] = useState([]);
            const [idClienteAtivo, setIdClienteAtivo] = useState(1);
            const [produtoSelecionadoId, setProdutoSelecionadoId] = useState(produtos[0]?.id || 0);
            const [quantidadeDesejada, setQuantidadeDesejada] = useState(1);
            const [descontoTexto, setDescontoTexto] = useState('0.00');

            // Cliente Modals
            const [mostrarModalCLiente, setMostrarModalCliente] = useState(false);
            const [clienteEditando, setClienteEditando] = useState(null);
            const [cliNome, setCliNome] = useState('');
            const [cliCpf, setCliCpf] = useState('');
            const [cliTelefone, setCliTelefone] = useState('');

            // Delete client toggle & Clear cart toggle
            const [mostrarExcluirCliente, setMostrarExcluirCliente] = useState(false);
            const [mostrarLimparCarrinho, setMostrarLimparCarrinho] = useState(false);

            const produtoAtivo = produtos.find(p => p.id === produtoSelecionadoId);
            const clienteAtivo = clientes.find(c => c.id === idClienteAtivo) || clientes[0];

            useEffect(() => {
                if (produtos.length > 0 && !produtos.find(p => p.id === produtoSelecionadoId)) {
                    setProdutoSelecionadoId(produtos[0].id);
                }
            }, [produtos]);

            const lidarComAdicionarItem = () => {
                if (!produtoAtivo) return;
                if (quantidadeDesejada <= 0) {
                    onShowToast("Insira uma quantidade maior que zero.", "erro");
                    return;
                }

                const itemExistente = carrinho.find(item => item.id === produtoAtivo.id);
                const qtdTotalNoCarrinho = itemExistente ? itemExistente.qtd : 0;

                if (qtdTotalNoCarrinho + quantidadeDesejada > produtoAtivo.estoque) {
                    onShowToast(`Disponível em estoque: ${produtoAtivo.estoque}.`, "erro");
                    return;
                }

                if (itemExistente) {
                    setCarrinho(carrinho.map(item => {
                        if (item.id === produtoAtivo.id) {
                            const novaQtd = item.qtd + quantidadeDesejada;
                            return { ...item, qtd: novaQtd, total: novaQtd * item.preco };
                        }
                        return item;
                    }));
                } else {
                    setCarrinho([...carrinho, {
                        id: produtoAtivo.id,
                        nome: produtoAtivo.nome,
                        qtd: quantidadeDesejada,
                        preco: produtoAtivo.preco,
                        total: quantidadeDesejada * produtoAtivo.preco,
                        imagemUrl: produtoAtivo.imagemUrl
                    }]);
                }
                setQuantidadeDesejada(1);
            };

            const subtotal = carrinho.reduce((sum, item) => sum + item.total, 0);
            const desconto = Math.max(0, parseFloat(descontoTexto.toString().replace(',', '.')) || 0);
            const total = Math.max(0, subtotal - desconto);

            const finalizarVenda = () => {
                if (carrinho.length === 0) {
                    onShowToast("Adicione peças ao checkout antes de finalizar.", "erro");
                    return;
                }

                // Chamar salvamento global
                onConfirmarVenda(clienteAtivo.nome, carrinho, total, desconto);
                onShowToast(`Venda faturada! Cliente: ${clienteAtivo.nome}`, "sucesso");
                setCarrinho([]);
                setDescontoTexto('0.00');
            };

            const abrirNovoCliente = () => {
                setClienteEditando(null);
                setCliNome('');
                setCliCpf('');
                setCliTelefone('');
                setMostrarModalCliente(true);
            };

            const abrirEditarCliente = () => {
                if (clienteAtivo.id === 1) {
                    onShowToast("O Consumidor Final padrão não pode ser editado.", "erro");
                    return;
                }
                setClienteEditando(clienteAtivo);
                setCliNome(clienteAtivo.nome);
                setCliCpf(clienteAtivo.cpf || '');
                setCliTelefone(clienteAtivo.telefone || '');
                setMostrarModalCliente(true);
            };

            const salvarClienteFicha = (e) => {
                e.preventDefault();
                if (!cliNome.trim()) {
                    onShowToast("O nome do cliente é obrigatório.", "erro");
                    return;
                }

                const payload = { nome: cliNome.trim(), cpf: cliCpf.replace(/\D/g, ''), telefone: cliTelefone.replace(/\D/g, '') };
                
                if (clienteEditando) {
                    onAtualizarCliente(clienteEditando.id, payload);
                    onShowToast("Ficha do cliente atualizada com sucesso!", "sucesso");
                } else {
                    onAdicionarCliente(payload);
                    onShowToast("Novo cliente cadastrado com sucesso!", "sucesso");
                }
                setMostrarModalCliente(false);
            };

            return (
                <div className="space-y-8 animate-fade-in text-left">
                    <div>
                        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Ponto de Venda (PDV)</h1>
                        <p className="text-slate-500 text-sm mt-1">Lançamento fiscal rápido e faturamento comercial.</p>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        {/* Formulário Lançar */}
                        <div className="lg:col-span-5 space-y-6">
                            {/* Sessão Cliente */}
                            <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente</span>
                                    <div className="flex gap-1">
                                        <button onClick={abrirNovoCliente} className="p-1 px-2.5 bg-bone hover:bg-dun text-ebony font-bold text-[11px] rounded-lg transition">+ Cadastrar</button>
                                        {clienteAtivo.id !== 1 && (
                                            <>
                                                <button onClick={abrirEditarCliente} className="p-1 px-2 bg-slate-50 hover:bg-slate-100 text-slate-500 font-bold text-[11px] rounded-lg transition">Editar</button>
                                                <button onClick={() => setMostrarExcluirCliente(true)} className="p-1 px-2 bg-rose-50 text-rose-600 font-bold text-[11px] rounded-lg transition animate-pulse">Excluir</button>
                                            </>
                                        )}
                                    </div>
                                </div>
                                <select value={idClienteAtivo} onChange={(e) => setIdClienteAtivo(Number(e.target.value))} className="w-full bg-slate-50 border border-slate-150 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                                    {clientes.map(cli => (
                                        <option key={cli.id} value={cli.id}>
                                            {cli.nome} {cli.cpf ? `[CPF: ${cli.cpf}]` : ''}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            {/* Escolher Produto */}
                            <div className="bg-white p-5 rounded-xl border border-slate-100 shadow-sm space-y-4">
                                <span className="text-xs font-bold text-slate-400 uppercase tracking-wider block">Inserir Produto na Sacola</span>
                                <div>
                                    <label className="block text-xs font-semibold text-slate-500 mb-1.5">Escolher item catalogal</label>
                                    <select value={produtoSelecionadoId} onChange={(e) => { setProdutoSelecionadoId(Number(e.target.value)); setQuantidadeDesejada(1); }} className="w-full bg-slate-50 border border-slate-150 rounded-lg px-3 py-2.5 text-sm focus:outline-none">
                                        {produtos.map(prod => (
                                            <option key={prod.id} value={prod.id} disabled={prod.estoque <= 0}>
                                                {prod.nome} (Estoque: {prod.estoque}) - R$ {prod.preco.toFixed(2)}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                {produtoAtivo && (
                                    <div className="flex gap-3.5 p-3.5 bg-slate-50/50 rounded-xl border border-dashed border-slate-150 items-center">
                                        <img src={produtoAtivo.imagemUrl} alt={produtoAtivo.nome} className="w-14 h-14 object-cover rounded-md border border-slate-100 shrink-0" />
                                        <div>
                                            <h4 className="text-xs font-bold text-slate-900">{produtoAtivo.nome}</h4>
                                            <p className="text-[10px] text-slate-400 mt-0.5">TAM: {produtoAtivo.tamanho || "M"} | Categoria: {produtoAtivo.categoria || "Geral"}</p>
                                            <p className="text-xs font-bold text-ebony mt-1 font-mono">R$ {produtoAtivo.preco.toFixed(2)}</p>
                                        </div>
                                    </div>
                                )}

                                <div className="grid grid-cols-2 gap-4 items-end">
                                    <div>
                                        <label className="block text-xs font-semibold text-slate-500 mb-1">Quantidade</label>
                                        <div className="flex items-center">
                                            <button type="button" onClick={() => setQuantidadeDesejada(Math.max(1, quantidadeDesejada - 1))} className="p-2 bg-slate-100 rounded-l-lg transition focus:outline-none">-</button>
                                            <input type="number" min="1" value={quantidadeDesejada} onChange={(e) => setQuantidadeDesejada(Math.max(1, parseInt(e.target.value) || 1))} className="w-full bg-slate-55 border-y border-slate-100 text-center py-1.5 font-mono text-sm" />
                                            <button type="button" onClick={() => setQuantidadeDesejada(quantidadeDesejada + 1)} className="p-2 bg-slate-100 rounded-r-lg transition focus:outline-none">+</button>
                                        </div>
                                    </div>
                                    <button onClick={lidarComAdicionarItem} className="w-full bg-ebony hover:bg-reseda text-white font-bold py-2.5 rounded-lg text-xs uppercase tracking-wider transition cursor-pointer">Incluir Peça</button>
                                </div>
                            </div>
                        </div>

                        {/* Sacola Lançamentos */}
                        <div className="lg:col-span-7 bg-white p-6 rounded-xl border border-slate-100 shadow-sm flex flex-col justify-between min-h-[460px]">
                            <div>
                                <div className="flex justify-between items-center pb-4 border-b border-slate-50">
                                    <div className="flex items-center gap-2 text-slate-900 font-serif">
                                        <i data-lucide="shopping-cart" className="text-ebony w-5 h-5"></i>
                                        <h3 className="text-lg">Carrinho de Compra</h3>
                                    </div>
                                    {carrinho.length > 0 && (
                                        <button onClick={() => setMostrarLimparCarrinho(true)} className="text-xs font-bold text-slate-400 hover:text-[#a64b2a] cursor-pointer">Limpar Carrinho</button>
                                    )}
                                </div>

                                {carrinho.length === 0 ? (
                                    <div className="py-20 text-center text-slate-400 space-y-3">
                                        <i data-lucide="package" className="mx-auto text-slate-200 w-12 h-12"></i>
                                        <p className="text-sm font-semibold">Nenhum item na sacola de compras.</p>
                                    </div>
                                ) : (
                                    <div className="divide-y divide-slate-50 max-h-[220px] overflow-y-auto mt-4 text-xs">
                                        {carrinho.map(item => (
                                            <div key={item.id} className="py-3.5 flex items-center justify-between">
                                                <div className="flex items-center gap-3">
                                                    <img src={item.imagemUrl} alt={item.nome} className="w-10 h-10 object-cover rounded border border-slate-100 shrink-0" />
                                                    <div>
                                                        <p className="font-bold text-slate-800">{item.nome}</p>
                                                        <p className="text-[10px] text-slate-400 font-mono mt-0.5">{item.qtd}x de R$ {item.preco.toFixed(2)}</p>
                                                    </div>
                                                </div>
                                                <div className="flex items-center gap-4">
                                                    <p className="font-bold text-slate-800 font-mono">R$ {item.total.toFixed(2)}</p>
                                                    <button onClick={() => setCarrinho(carrinho.filter(i => i.id !== item.id))} className="text-slate-400 hover:text-[#a64b2a]"><i data-lucide="trash-2" className="w-4 h-4"></i></button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>

                            {/* Fechamento Financas */}
                            <div className="pt-6 border-t border-slate-50 mt-8 space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-xs text-slate-550 font-semibold uppercase flex items-center gap-1"><i data-lucide="tag" className="w-4 h-4 text-slate-400"></i> Cupom de Desconto:</span>
                                    <div className="relative max-w-[120px]">
                                        <span className="absolute left-2.5 top-2 text-slate-400 font-bold font-mono text-xs">R$</span>
                                        <input type="text" value={descontoTexto} onChange={(e) => setDescontoTexto(e.target.value)} className="w-full text-right bg-slate-50 border border-slate-100 rounded px-2.5 py-1.5 focus:outline-none font-mono font-bold text-xs" />
                                    </div>
                                </div>

                                <div className="bg-slate-50 p-4 rounded-xl border border-slate-100 space-y-1.5 font-sans">
                                    <div className="flex justify-between text-xs text-slate-500 font-bold">
                                        <span>Subtotal Sacola:</span>
                                        <span className="font-mono">R$ {subtotal.toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between text-xs text-[#a64b2a] font-bold">
                                        <span>Desconto Concedido:</span>
                                        <span className="font-mono">- R$ {desconto.toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between text-sm font-bold pt-2 border-t border-slate-100 text-slate-900">
                                        <span className="uppercase text-[10px] text-slate-400 font-sans tracking-wider">Valor Líquido a Receber</span>
                                        <span className="text-xl text-ebony font-bold font-mono">R$ {total.toFixed(2)}</span>
                                    </div>
                                </div>

                                <button onClick={finalizarVenda} className="w-full bg-ebony hover:bg-reseda text-white font-bold py-3.5 rounded-xl text-xs tracking-widest uppercase transition flex items-center justify-center gap-2 shadow-md cursor-pointer">
                                    <i data-lucide="credit-card" className="text-sage w-4 h-4"></i>
                                    <span>Concluir e Receber</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* MODAL CLIENTE */}
                    {mostrarModalCLiente && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs" onClick={() => setMostrarModalCliente(false)} />
                            <div className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl p-6 space-y-4 animate-scale-up text-left z-10 text-slate-800">
                                <h3 className="text-lg font-serif font-bold text-slate-900">{clienteEditando ? "Editar Ficha de Cliente" : "Cadastrar Novo Cliente"}</h3>
                                <form onSubmit={salvarClienteFicha} className="space-y-4 text-xs">
                                    <div>
                                        <label className="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Nome Completo *</label>
                                        <input type="text" required value={cliNome} onChange={(e) => setCliNome(e.target.value)} placeholder="Amanda Duarte" className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 focus:outline-none focus:ring-1 focus:ring-ebony" />
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-slate-400 mb-1 uppercase">CPF (Apenas números)</label>
                                        <input type="text" value={cliCpf} onChange={(e) => setCliCpf(e.target.value)} placeholder="12345678901" className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 font-mono focus:outline-none" />
                                    </div>
                                    <div>
                                        <label className="block text-[10px] font-bold text-slate-400 mb-1 uppercase">Telefone (Apenas números)</label>
                                        <input type="text" value={cliTelefone} onChange={(e) => setCliTelefone(e.target.value)} placeholder="11912345678" className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 font-mono focus:outline-none" />
                                    </div>
                                    <div className="flex justify-end gap-2.5 pt-4 border-t border-slate-50">
                                        <button type="button" onClick={() => setMostrarModalCliente(false)} className="px-3.5 py-1.5 bg-slate-100 text-slate-600 rounded-lg text-xs font-semibold">Cancelar</button>
                                        <button type="submit" className="px-4.5 py-1.5 bg-ebony text-white rounded-lg text-xs font-bold uppercase tracking-wider">Gravar Ficha</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    )}

                    {/* CONFIRMAR EXCLUIR CLIENTE */}
                    {mostrarExcluirCliente && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60" onClick={() => setMostrarExcluirCliente(false)} />
                            <div className="relative w-full max-w-sm bg-white rounded-2xl p-6 text-center z-10 text-slate-800 shadow-xl space-y-4">
                                <div className="mx-auto w-12 h-12 bg-rose-50 rounded-full flex items-center justify-center text-[#a64b2a]"><i data-lucide="alert-triangle"></i></div>
                                <h3 className="text-lg font-serif font-bold text-slate-900">Excluir Cadastro</h3>
                                <p className="text-slate-500 text-xs">Excluir permanentemente o cadastro do cliente <b>"{clienteAtivo.nome}"</b>?</p>
                                <div className="flex gap-2.5">
                                    <button onClick={() => setMostrarExcluirCliente(false)} className="w-full py-2.5 bg-slate-100 text-slate-650 font-semibold rounded-xl text-xs">Retroceder</button>
                                    <button onClick={() => {
                                        onExcluirCliente(clienteAtivo.id);
                                        setIdClienteAtivo(1);
                                        setMostrarExcluirCliente(false);
                                        onShowToast("Cliente excluído!", "sucesso");
                                    }} className="w-full py-2.5 bg-[#a64b2a] text-white font-bold rounded-xl text-xs uppercase">Sim, Excluir</button>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* CONFIRMAR DELETAR VENDA */}
                    {mostrarLimparCarrinho && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60" onClick={() => setMostrarLimparCarrinho(false)} />
                            <div className="relative w-full max-w-sm bg-white rounded-2xl p-6 text-center z-10 text-slate-800 shadow-xl space-y-4">
                                <div className="mx-auto w-12 h-12 bg-rose-50 text-red-600 rounded-full flex items-center justify-center"><i data-lucide="trash-2"></i></div>
                                <h3 className="text-lg font-serif font-bold text-slate-900">Limpar Sacola</h3>
                                <p className="text-slate-500 text-xs">Esvaziar todos os itens inseridos da sacola atual?</p>
                                <div className="flex gap-2.5">
                                    <button onClick={() => setMostrarLimparCarrinho(false)} className="w-full py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs">Retroceder</button>
                                    <button onClick={() => {
                                        setCarrinho([]);
                                        setDescontoTexto('0.00');
                                        setMostrarLimparCarrinho(false);
                                        onShowToast("Sacola de compras limpa!", "sucesso");
                                    }} className="w-full py-2.5 bg-[#a64b2a] text-white font-bold rounded-xl text-xs uppercase">Sim, Limpar</button>
                                </div>
                            </div>
                        </div>
                    )}
                    <TriggerIcons />
                </div>
            );
        }

        // --- REPORTS ---
        function Reports({ vendas, produtos, onExcluirVenda, onShowToast }) {
            const [linhaAbertaId, setLinhaAbertaId] = useState(null);
            const [vendaExcluirId, setVendaExcluirId] = useState(null);

            const formatarData = (iso) => {
                try {
                    const data = new Date(iso);
                    return data.toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
                } catch {
                    return iso;
                }
            };

            return (
                <div className="space-y-8 animate-fade-in text-left">
                    <div>
                        <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Relatório de Transações</h1>
                        <p className="text-slate-500 text-sm mt-1">Registros de caixa, livro caixa e faturamento efetuado.</p>
                    </div>

                    {vendas.length === 0 ? (
                        <div className="bg-white rounded-xl border border-slate-100 text-center py-20 px-4">
                            <i data-lucide="file-text" className="mx-auto text-stone-300 w-12 h-12 mb-4"></i>
                            <h3 className="text-lg font-medium text-slate-700">Sem relatórios disponíveis</h3>
                        </div>
                    ) : (
                        <div className="bg-white rounded-xl border border-slate-100 shadow-sm overflow-hidden">
                            <div className="p-4 bg-slate-50 border-b border-bone flex items-center justify-between text-xs text-slate-400 font-bold uppercase tracking-wider">
                                <span>Livro de Registro Financeiro</span>
                                <span>{vendas.length} faturadas</span>
                            </div>

                            <div className="divide-y divide-slate-100 font-sans">
                                {vendas.map((venda) => {
                                    const estaAberto = linhaAbertaId === venda.id;
                                    const totalEtiquetas = venda.itens.reduce((sum, item) => sum + item.quantidade, 0);

                                    return (
                                        <div key={venda.id} className="transition-all duration-200">
                                            <div onClick={() => setLinhaAbertaId(estaAberto ? null : venda.id)} className="flex flex-col md:flex-row md:items-center justify-between p-5 gap-4 cursor-pointer select-none hover:bg-slate-50/30">
                                                <div className="flex items-center gap-4">
                                                    <span className="w-10 h-10 rounded-full bg-bone text-ebony font-bold font-mono text-xs flex items-center justify-center shrink-0 border border-slate-100 shadow-xs">#{venda.id}</span>
                                                    <div>
                                                        <span className="font-bold text-slate-900 block">{venda.clienteNome}</span>
                                                        <div className="flex items-center gap-3 text-xs text-slate-400 mt-1">
                                                            <span className="flex items-center gap-1"><i data-lucide="calendar" className="w-3.5 h-3.5"></i> {formatarData(venda.dataCriacao)}</span>
                                                            <span className="flex items-center gap-1 font-bold text-slate-650"><i data-lucide="package" className="w-3.5 h-3.5 text-slate-350"></i> {totalEtiquetas} un</span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div className="flex items-center justify-between md:justify-end gap-6 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100">
                                                    <div className="text-left md:text-right">
                                                        <span className="text-[10px] text-slate-400 font-bold uppercase">Total Faturado</span>
                                                        <span className="text-lg font-bold text-ebony font-mono block">R$ {venda.total.toFixed(2)}</span>
                                                    </div>
                                                    <i data-lucide={estaAberto ? "chevron-up" : "chevron-down"} className="text-slate-450 w-5 h-5"></i>
                                                </div>
                                            </div>

                                            {/* Expandable Recibo */}
                                            {estaAberto && (
                                                <div className="p-5 pt-0 bg-slate-50/50 border-t border-slate-100">
                                                    <h5 className="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2.5 mt-3">Detalhamento dos Itens Comprados</h5>
                                                    <div className="bg-white border rounded-xl overflow-hidden shadow-xs text-xs">
                                                        <div className="grid grid-cols-12 bg-slate-50 p-2.5 border-b font-bold text-slate-400 uppercase tracking-wider text-[10px]">
                                                            <span className="col-span-6 text-left">Especificação da Peça</span>
                                                            <span className="col-span-2 text-center">Quant.</span>
                                                            <span className="col-span-2 text-right">Unitário</span>
                                                            <span className="col-span-2 text-right">Subtotal</span>
                                                        </div>
                                                        <div className="divide-y divide-slate-50 font-medium">
                                                            {venda.itens.map((item, idx) => {
                                                                const prodRef = produtos.find(p => p.id === item.idProduto);
                                                                const imgUrl = prodRef?.imagemUrl || TEMPLATE_IMAGENS[0].url;
                                                                return (
                                                                    <div key={idx} className="grid grid-cols-12 p-3 items-center hover:bg-slate-50/20">
                                                                        <div className="col-span-6 flex items-center gap-3">
                                                                            <img src={imgUrl} alt={item.nomeProduto} className="w-9 h-9 object-cover rounded border" />
                                                                            <span className="font-bold text-slate-800">{item.nomeProduto}</span>
                                                                        </div>
                                                                        <span className="col-span-2 text-center font-bold font-mono">{item.quantidade} un</span>
                                                                        <span className="col-span-2 text-right font-mono text-slate-400">R$ {item.precoUnitario.toFixed(2)}</span>
                                                                        <span className="col-span-2 text-right font-bold text-slate-900 font-mono">R$ {(item.quantidade * item.precoUnitario).toFixed(2)}</span>
                                                                    </div>
                                                                );
                                                            })}
                                                        </div>

                                                        {/* Sumario Rodapé Recibo */}
                                                        <div className="bg-slate-50/80 p-3.5 flex flex-col sm:flex-row items-center justify-between border-t gap-3">
                                                            <button onClick={(e) => { e.stopPropagation(); setVendaExcluirId(venda.id); }} className="flex items-center gap-1 text-[11px] font-bold text-[#a64b2a] bg-rose-50 px-3.5 py-1.5 rounded-lg border border-rose-100 hover:bg-rose-100/55 cursor-pointer">
                                                                <i data-lucide="trash-2" className="w-3.5 h-3.5"></i> Excluir Cupom
                                                            </button>
                                                            <div className="flex gap-6 text-right ml-auto">
                                                                {venda.desconto > 0 && (
                                                                    <div>
                                                                        <span className="text-[10px] text-slate-405 font-bold uppercase block">Desconto</span>
                                                                        <span className="text-emerald-600 font-bold font-mono">- R$ {venda.desconto.toFixed(2)}</span>
                                                                    </div>
                                                                )}
                                                                <div className="border-l pl-5">
                                                                    <span className="text-[10px] text-slate-405 font-bold uppercase block">Líquido Pago</span>
                                                                    <span className="text-indigo-950 font-bold text-sm font-mono text-ebony">R$ {venda.total.toFixed(2)}</span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                    )}

                    {/* CONFIRMAÇÃO REMOVER HISTÓRICO DE COMPRA */}
                    {vendaExcluirId !== null && (
                        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                            <div className="absolute inset-0 bg-slate-900/60" onClick={() => setVendaExcluirId(null)} />
                            <div className="relative w-full max-w-sm bg-white rounded-2xl p-6 text-center shadow-xl space-y-4 text-slate-800 z-10">
                                <div className="mx-auto w-12 h-12 bg-rose-50 text-[#a64b2a] rounded-full flex items-center justify-center"><i data-lucide="alert-triangle" className="w-6 h-6 animate-pulse"></i></div>
                                <h3 className="text-lg font-serif font-bold text-slate-900">Excluir Relatório</h3>
                                <p className="text-slate-500 text-xs">Excluir permanentemente o livro de registro <b>#{vendaExcluirId}</b>? Esta ação é irreversível.</p>
                                <div className="flex gap-2.5">
                                    <button onClick={() => setVendaExcluirId(null)} className="w-full py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl text-xs">Retroceder</button>
                                    <button onClick={() => {
                                        onExcluirVenda(vendaExcluirId);
                                        onShowToast("Cópia fiscal removida com sucesso!", "sucesso");
                                        setVendaExcluirId(null);
                                    }} className="w-full py-2.5 bg-[#a64b2a] text-white font-bold rounded-xl text-xs uppercase cursor-pointer">Confirmar</button>
                                </div>
                            </div>
                        </div>
                    )}
                    <TriggerIcons />
                </div>
            );
        }

        // --- COMPONENTE GERAL CONTROLADOR (MAIN APP) ---
        function App() {
            const [state, setState] = useState({ produtos: [], clientes: [], vendas: [] });
            const [usuarioLogado, setUsuarioLogado] = useState(null);
            
            // Login credentials form
            const [loginUsuario, setLoginUsuario] = useState('');
            const [loginSenha, setLoginSenha] = useState('');
            const [erroLogin, setErroLogin] = useState('');

            // Navegação
            const [menuAtivo, setMenuAtivo] = useState('dashboard');
            const [mobileSidebarAberta, setMobileSidebarAberta] = useState(false);

            // Toasts control
            const [toastMsg, setToastMsg] = useState(null);

            const showToast = (texto, tipo = 'sucesso') => {
                setToastMsg({ texto, tipo });
            };

            // Fazer GET inicial para sincronizar com Python Database
            useEffect(() => {
                fetch("/api/state")
                    .then(res => res.json())
                    .then(data => {
                        setState({
                            produtos: data.produtos || [],
                            clientes: data.clientes || [],
                            vendas: data.vendas || []
                        });
                    })
                    .catch(e => {
                        console.error("Falha ao comunicar com o servidor Python. Operando em memória local temporária", e);
                        // Carregar localStorage fallback
                        const localP = localStorage.getItem('elegancia_prod') ? JSON.parse(localStorage.getItem('elegancia_prod')) : [];
                        const localC = localStorage.getItem('elegancia_cli') ? JSON.parse(localStorage.getItem('elegancia_cli')) : [];
                        const localV = localStorage.getItem('elegancia_vend') ? JSON.parse(localStorage.getItem('elegancia_vend')) : [];
                        setState({ produtos: localP, clientes: localC, vendas: localV });
                    });

                const cacheUser = localStorage.getItem('elegancia_usuario_atual');
                if (cacheUser) {
                    setUsuarioLogado(JSON.parse(cacheUser));
                }
            }, []);

            // Método geral para submeter atualização para a API python
            const persistirNovoEstado = (novoEstado) => {
                setState(novoEstado);
                fetch("/api/state", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(novoEstado)
                }).catch(err => {
                    console.error("Fallback gravação local storage:", err);
                });
                // Gravar copias no localstorage tb
                localStorage.setItem('elegancia_prod', JSON.stringify(novoEstado.produtos));
                localStorage.setItem('elegancia_cli', JSON.stringify(novoEstado.clientes));
                localStorage.setItem('elegancia_vend', JSON.stringify(novoEstado.vendas));
            };

            const lidarComLogin = (e) => {
                e.preventDefault();
                setErroLogin('');

                if (!loginUsuario.trim() || !loginSenha.trim()) {
                    setErroLogin('Por favor, digite seu usuário e senha.');
                    return;
                }

                const match = USERS.find(u => u.usuario === loginUsuario.toLowerCase().trim());
                if (match && loginSenha === '123') {
                    setUsuarioLogado(match);
                    localStorage.setItem('elegancia_usuario_atual', JSON.stringify(match));
                    
                    // Roteamento conforme o cargo correspondente
                    if (match.cargo === 'vendedor') setMenuAtivo('caixa');
                    else if (match.cargo === 'estoquista') setMenuAtivo('estoque');
                    else setMenuAtivo('dashboard');
                    
                    setLoginUsuario('');
                    setLoginSenha('');
                } else {
                    setErroLogin('Credenciais incorretas (Dica: use maria/123, carlos/123 ou patricia/123)');
                }
            };

            const lidarComLogout = () => {
                setUsuarioLogado(null);
                localStorage.removeItem('elegancia_usuario_atual');
            };

            // Métodos logísticos passados aos sub-componentes
            const onAdicionarProduto = (p) => {
                const novoId = state.produtos.length > 0 ? Math.max(...state.produtos.map(p2 => p2.id)) + 1 : 1;
                const novosProdutos = [...state.produtos, { id: novoId, ...p }];
                persistirNovoEstado({ ...state, produtos: novosProdutos });
            };

            const onAtualizarProduto = (id, p) => {
                const novosProdutos = state.produtos.map(item => item.id === id ? { id, ...p } : item);
                persistirNovoEstado({ ...state, produtos: novosProdutos });
            };

            const onExcluirProduto = (id) => {
                const novosProdutos = state.produtos.filter(p => p.id !== id);
                persistirNovoEstado({ ...state, produtos: novosProdutos });
            };

            const onAdicionarCliente = (c) => {
                const novoId = state.clientes.length > 0 ? Math.max(...state.clientes.map(c2 => c2.id)) + 1 : 1;
                const novosClientes = [...state.clientes, { id: novoId, ...c }];
                persistirNovoEstado({ ...state, clientes: novosClientes });
            };

            const onAtualizarCliente = (id, c) => {
                const novosClientes = state.clientes.map(item => item.id === id ? { id, ...c } : item);
                persistirNovoEstado({ ...state, clientes: novosClientes });
            };

            const onExcluirCliente = (id) => {
                const novosClientes = state.clientes.filter(c => c.id !== id);
                persistirNovoEstado({ ...state, clientes: novosClientes });
            };

            const onConfirmarVenda = (clienteNome, itensCarrinho, totalVenda, descontoVenda) => {
                const novoIdVenda = state.vendas.length > 0 ? Math.max(...state.vendas.map(v => v.id)) + 1 : 1;
                
                // Reduzir estoque na memória
                const novosProdutos = state.produtos.map(prod => {
                    const itemQtd = itensCarrinho.find(i => i.id === prod.id);
                    if (itemQtd) {
                        return { ...prod, estoque: Math.max(0, prod.estoque - itemQtd.qtd) };
                    }
                    return prod;
                });

                const novaVenda = {
                    id: novoIdVenda,
                    clienteNome,
                    total: totalVenda,
                    desconto: descontoVenda,
                    dataCriacao: new Date().toISOString(),
                    itens: itensCarrinho.map(item => ({
                        idProduto: item.id,
                        nomeProduto: item.nome,
                        quantidade: item.qtd,
                        precoUnitario: item.preco
                    }))
                };

                persistirNovoEstado({
                    ...state,
                    produtos: novosProdutos,
                    vendas: [novaVenda, ...state.vendas]
                });
            };

            const onExcluirVenda = (id) => {
                const novasVendas = state.vendas.filter(v => v.id !== id);
                persistirNovoEstado({ ...state, vendas: novasVendas });
            };

            // Filtrados Tabs por Cargos
            const tabsDisponiveis = useMemo(() => {
                if (!usuarioLogado) return [];
                const cargo = usuarioLogado.cargo;
                if (cargo === 'gerente') {
                    return [
                        { id: 'dashboard', nome: 'Dashboard', icone: "trending-up" },
                        { id: 'estoque', nome: 'Estoque de Roupas', icone: "package" },
                        { id: 'caixa', nome: 'Caixa (PDV)', icone: "shopping-cart" },
                        { id: 'relatorios', nome: 'Relatórios de Vendas', icone: "file-text" }
                    ];
                } else if (cargo === 'vendedor') {
                    return [
                        { id: 'caixa', nome: 'Caixa (PDV)', icone: "shopping-cart" }
                    ];
                } else if (cargo === 'estoquista') {
                    return [
                        { id: 'estoque', nome: 'Estoque de Roupas', icone: "package" }
                    ];
                }
                return [];
            }, [usuarioLogado]);

            const renderizarSecaoExibir = () => {
                switch(menuAtivo) {
                    case 'dashboard': return <Dashboard produtos={state.produtos} vendas={state.vendas} />;
                    case 'estoque': return <Inventory produtos={state.produtos} onAdicionar={onAdicionarProduto} onAtualizar={onAtualizarProduto} onExcluir={onExcluirProduto} podeEditar={usuarioLogado?.cargo === 'gerente' || usuarioLogado?.cargo === 'estoquista'} onShowToast={showToast} />;
                    case 'caixa': return <PDV produtos={state.produtos} clientes={state.clientes} onAdicionarCliente={onAdicionarCliente} onAtualizarCliente={onAtualizarCliente} onExcluirCliente={onExcluirCliente} onConfirmarVenda={onConfirmarVenda} onShowToast={showToast} />;
                    case 'relatorios': return <Reports vendas={state.vendas} produtos={state.produtos} onExcluirVenda={onExcluirVenda} onShowToast={showToast} />;
                    default: return <Dashboard produtos={state.produtos} vendas={state.vendas} />;
                }
            };

            // --- TELA DE LOGIN ---
            if (!usuarioLogado) {
                return (
                    <div className="min-h-screen bg-bone flex flex-col items-center justify-center p-4 relative overflow-hidden font-sans">
                        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[450px] h-[450px] bg-reseda/10 blur-[100px] rounded-full pointer-events-none" />
                        <div className="w-full max-w-md z-10 space-y-6">
                            <div className="text-center space-y-1">
                                <h1 className="text-4xl text-ebony font-serif tracking-tight font-medium">Elegância Premium</h1>
                                <p className="text-reseda text-xs uppercase tracking-wider font-bold">Módulo Comercial de Vestuário - Python Local</p>
                            </div>

                            <div className="bg-white border rounded-2xl p-8 shadow-xl relative text-left">
                                <form onSubmit={lidarComLogin} className="space-y-4">
                                    <div>
                                        <label className="block text-[10px] font-bold text-reseda uppercase tracking-wider mb-1.5">Usuário</label>
                                        <div className="relative">
                                            <i data-lucide="user" className="absolute left-3.5 top-3 text-reseda/60 w-4 h-4"></i>
                                            <input type="text" required value={loginUsuario} onChange={e => setLoginUsuario(e.target.value)} placeholder="Dica: maria" className="w-full bg-bone/10 border text-slate-800 pl-10 pr-4 py-2 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-ebony" />
                                        </div>
                                    </div>

                                    <div>
                                        <label className="block text-[10px] font-bold text-reseda uppercase tracking-wider mb-1.5">Senha</label>
                                        <div className="relative">
                                            <i data-lucide="lock" className="absolute left-3.5 top-3 text-reseda/60 w-4 h-4"></i>
                                            <input type="password" required value={loginSenha} onChange={e => setLoginSenha(e.target.value)} placeholder="Senha (padrão: 123)" className="w-full bg-bone/10 border text-slate-800 pl-10 pr-4 py-2 rounded-xl text-sm focus:outline-none focus:ring-1 focus:ring-ebony" />
                                        </div>
                                    </div>

                                    {erroLogin && (
                                        <div className="bg-rose-50 border border-rose-250 text-rose-700 p-3 rounded-lg text-xs font-semibold text-center">{erroLogin}</div>
                                    )}

                                    <button type="submit" className="w-full bg-ebony hover:bg-reseda text-white font-bold py-3 rounded-xl text-xs tracking-widest uppercase transition shadow mt-4 cursor-pointer">Ingressar na Loja</button>
                                </form>
                            </div>
                        </div>
                        <TriggerIcons />
                    </div>
                );
            }

            // --- HOME DO SISTEMA CONECTADO ---
            return (
                <div className="min-h-screen flex flex-col md:flex-row font-sans text-slate-800 bg-stone-50/40">
                    <aside className="hidden md:flex flex-col w-64 bg-ebony text-bone shrink-0 justify-between self-stretch border-r border-[#3a412e]">
                        <div className="space-y-6 py-6 px-5">
                            <div className="text-left space-y-1 border-b border-reseda/20 pb-4 px-1">
                                <h2 className="text-xl font-serif text-white tracking-tight">Elegância Premium</h2>
                                <div className="flex items-center gap-1.5 text-[9px] text-[#A4AC86] font-bold uppercase tracking-wider">
                                    <span className="w-1.5 h-1.5 rounded-full bg-sage" />
                                    <span>Venda Local (Python)</span>
                                </div>
                            </div>

                            <div className="bg-reseda/15 border border-reseda/20 p-3.5 rounded-xl text-left">
                                <span className="text-[10px] font-bold text-[#A4AC86] uppercase tracking-widest block">Operador Ativo</span>
                                <span className="text-sm font-bold text-white block truncate">{usuarioLogado.nome}</span>
                                <span className="inline-flex text-[9px] font-bold uppercase px-2 py-0.5 rounded bg-sage/20 text-[#EBE3D2] border border-sage/30 mt-1.5">{usuarioLogado.cargo}</span>
                            </div>

                            <nav className="space-y-1 text-xs uppercase tracking-wider font-bold">
                                {tabsDisponiveis.map(tab => (
                                    <button
                                        key={tab.id}
                                        onClick={() => setMenuAtivo(tab.id)}
                                        className={`w-full flex items-center gap-3 px-3.5 py-3 rounded-lg text-left transition duration-150 cursor-pointer ${
                                            menuAtivo === tab.id ? 'bg-reseda text-white shadow-sm' : 'text-[#CCBFA3] hover:text-white hover:bg-reseda/20'
                                        }`}
                                    >
                                        <i data-lucide={tab.icone} className="w-4 h-4"></i>
                                        <span>{tab.nome}</span>
                                    </button>
                                ))}
                            </nav>
                        </div>

                        <div className="p-5 border-t border-reseda/15">
                            <button onClick={lidarComLogout} className="w-full flex items-center justify-center gap-2 bg-reseda/10 hover:bg-reseda/20 border border-reseda/20 text-[#CCBFA3] hover:text-white font-bold py-2 rounded-xl text-xs tracking-wider uppercase transition cursor-pointer">
                                <i data-lucide="log-out" className="w-3.5 h-3.5"></i>
                                <span>Sair do Sistema</span>
                            </button>
                        </div>
                    </aside>

                    {/* Header Mobile */}
                    <header className="md:hidden bg-ebony text-white p-4 flex items-center justify-between border-b border-reseda/30">
                        <div>
                            <h2 className="font-serif text-lg">Elegância Premium</h2>
                            <p className="text-[9px] text-sage tracking-wider font-bold uppercase">{usuarioLogado.cargo}</p>
                        </div>
                        <button onClick={() => setMobileSidebarAberta(!mobileSidebarAberta)} className="p-1.5 bg-reseda/30 rounded-lg text-slate-200 border border-reseda/20">
                            <i data-lucide={mobileSidebarAberta ? "x" : "menu"} className="w-5 h-5"></i>
                        </button>
                    </header>

                    {/* Menu Mobile Drawer */}
                    {mobileSidebarAberta && (
                        <div className="md:hidden bg-ebony text-white flex flex-col p-4 border-b border-reseda/30 space-y-3.5 select-none text-left">
                            <div className="text-xs text-dun border-b border-reseda/15 pb-2">Logado em: <b>{usuarioLogado.nome}</b></div>
                            <nav className="flex flex-col gap-1 text-xs uppercase tracking-wider font-bold">
                                {tabsDisponiveis.map(tab => (
                                    <button key={tab.id} onClick={() => { setMenuAtivo(tab.id); setMobileSidebarAberta(false); }} className={`w-full flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-left ${menuAtivo === tab.id ? 'bg-reseda text-white' : 'text-dun hover:text-white'}`}>
                                        <i data-lucide={tab.icone} className="w-4 h-4"></i>
                                        <span>{tab.nome}</span>
                                    </button>
                                ))}
                            </nav>
                            <button onClick={() => { setMobileSidebarAberta(false); lidarComLogout(); }} className="w-full flex items-center justify-center gap-2 bg-reseda/20 border border-reseda/30 text-dun hover:text-white py-2 rounded-lg text-xs font-bold uppercase"><i data-lucide="log-out" className="w-4 h-4"></i> Sair</button>
                        </div>
                    )}

                    <main className="flex-1 p-6 md:p-10 max-w-7xl mx-auto w-full">
                        {renderizarSecaoExibir()}
                    </main>

                    {toastMsg && <Toast texto={toastMsg.texto} tipo={toastMsg.tipo} onClose={() => setToastMsg(null)} />}
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

def main():
    # Detecta se a porta está sendo passada via parâmetro
    port = PORT
    if len(sys.argv) > 1:
        try:
            port = int(sys.argv[1])
        except ValueError:
            pass

    print("=" * 60)
    print(" INICIALIZANDO SERVIDOR LOCAL DO ELEGÂNCIA PREMIUM ")
    print("=" * 60)
    print(f" Servidor iniciado com sucesso!")
    print(f" Banco de dados local: '{DATA_FILE}'")
    print(f" Acesse localmente em: http://localhost:{port}")
    print(" Para encerrar o servidor, pressione: CTRL + C")
    print("=" * 60)

    # Inicia o servidor simples
    with socketserver.TCPServer(("0.0.0.0", port), EleganciaRequestHandler) as httpd:
        try:
            httpd.serve_forever()
        except KeyboardInterrupt:
            print("\\nServidor encerrado de forma segura.")

if __name__ == "__main__":
    main()
