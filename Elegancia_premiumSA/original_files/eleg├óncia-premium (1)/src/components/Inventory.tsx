import React, { useState } from 'react';
import { motion, AnimatePresence } from 'motion/react';
import { PlusCircle, Edit3, Trash2, Search, Image as ImageIcon, AlertTriangle, AlertCircle, Eye, SlidersHorizontal, Grid, List } from 'lucide-react';
import { Produto } from '../types';

interface InventoryProps {
  produtos: Produto[];
  onAdicionar: (p: Omit<Produto, 'id'>) => void;
  onAtualizar: (id: number, p: Omit<Produto, 'id'>) => void;
  onExcluir: (id: number) => void;
  podeEditar: boolean;
}

const TEMPLATE_IMAGENS = [
  { nome: "Saia Jeans", url: "https://misleading-indigo-rrfktsvo.edgeone.app/SAIA.jpg" },
  { nome: "Bolsa Azul Elegante", url: "https://decent-sapphire-pv9iwk9o.edgeone.app/bolsa.jpg" },
  { nome: "Oculos Elegante", url: "https://big-amber-aonkcumd.edgeone.app/oculos.jpg" },
  { nome: "Tenis Adidas Vermelho", url: "https://visible-white-bpzhicio.edgeone.app/tenis.jpg" },
  { nome: "Calça Jenas Preta", url: "https://devoted-salmon-wygt31hm.edgeone.app/calca.jpg" },
  { nome: "Vestido Off-White Curto", url: "https://limited-orange-cz77dz82.edgeone.app/vestido.jpg" },
  { nome: "Vestido Florido Branco e Azul", url: "https://incredible-lavender-aoqkgml2.edgeone.app/vestido2.jpg" },
  { nome: "Calça Moletom Cinza", url: "https://sound-bronze-og81vhsv.edgeone.app/calça%202.jpg" },
  { nome: "Salto Alto Preto", url: "https://wandering-scarlet-6oj4heo4.edgeone.app/salto.jpg" },
  { nome: "Casaco Couro Vermelho", url: "https://fundamental-bronze-dxejkhvn.edgeone.app/casaco2.jpg" },
  { nome: "Bolsa Azul", url: "https://uninterested-aquamarine-m4ergnn6.edgeone.app/bolsa2.jpg" },
  { nome: "Saia Branca", url: "https://numerous-apricot-pzyxj1tv.edgeone.app/saia%202.jpg" }
];

export default function Inventory({ produtos, onAdicionar, onAtualizar, onExcluir, podeEditar }: InventoryProps) {
  const [busca, setBusca] = useState('');
  const [categoriaAtiva, setCategoriaAtiva] = useState('Todas');
  const [modVisualizacao, setModVisualizacao] = useState<'grade' | 'tabela'>('grade');
  const [mostrarModal, setMostrarModal] = useState(false);
  const [editandoId, setEditandoId] = useState<number | null>(null);

  // Campos do Formulário
  const [formNome, setFormNome] = useState('');
  const [formPreco, setFormPreco] = useState('');
  const [formEstoque, setFormEstoque] = useState('');
  const [formCategoria, setFormCategoria] = useState('Camisas');
  const [formTamanho, setFormTamanho] = useState('M');
  const [formImagemUrl, setFormImagemUrl] = useState('');

  // Notificação e Confirmação de Exclusão
  const [notificacao, setNotificacao] = useState<{ texto: string; tipo: 'sucesso' | 'erro' } | null>(null);
  const [produtoExclusao, setProdutoExclusao] = useState<{ id: number; nome: string } | null>(null);

  const mostrarNotificacao = (texto: string, tipo: 'sucesso' | 'erro' = 'sucesso') => {
    setNotificacao({ texto, tipo });
    setTimeout(() => {
      setNotificacao(current => current?.texto === texto ? null : current);
    }, 4000);
  };

  // Sincronizar categorias conhecidas
  const categorias = ["Todas", ...Array.from(new Set(produtos.map(p => p.categoria || "Outros")))];

  // Filtros
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

  const abrirEditarModal = (prod: Produto) => {
    setEditandoId(prod.id);
    setFormNome(prod.nome);
    setFormPreco(prod.preco.toString());
    setFormEstoque(prod.estoque.toString());
    setFormCategoria(prod.categoria || 'Outros');
    setFormTamanho(prod.tamanho || 'M');
    setFormImagemUrl(prod.imagemUrl);
    setMostrarModal(true);
  };

  const lidarComSalvar = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formNome.trim() || !formPreco || !formEstoque) {
      mostrarNotificacao("Por favor, preencha todos os campos obrigatórios.", "erro");
      return;
    }

    const precoNum = parseFloat(formPreco.replace(',', '.'));
    const estoqueNum = parseInt(formEstoque);

    if (isNaN(precoNum) || precoNum < 0) {
      mostrarNotificacao("Insira um preço válido.", "erro");
      return;
    }
    if (isNaN(estoqueNum) || estoqueNum < 0) {
      mostrarNotificacao("Insira um número de estoque válido.", "erro");
      return;
    }

    const imgUrl = formImagemUrl.trim() || TEMPLATE_IMAGENS[0].url;

    const payload = {
      nome: formNome.trim(),
      preco: precoNum,
      estoque: estoqueNum,
      categoria: formCategoria,
      tamanho: formTamanho,
      imagemUrl: imgUrl
    };

    if (editandoId !== null) {
      onAtualizar(editandoId, payload);
      mostrarNotificacao(`Produto "${payload.nome}" atualizado com sucesso!`, "sucesso");
    } else {
      onAdicionar(payload);
      mostrarNotificacao(`Produto "${payload.nome}" cadastrado com sucesso!`, "sucesso");
    }

    setMostrarModal(false);
  };

  const confirmarExclusao = (id: number, nome: string) => {
    setProdutoExclusao({ id, nome });
  };

  const lidarComConfirmarExclusao = () => {
    if (produtoExclusao) {
      onExcluir(produtoExclusao.id);
      mostrarNotificacao(`Produto "${produtoExclusao.nome}" removido com sucesso!`, "sucesso");
      setProdutoExclusao(null);
    }
  };

  return (
    <div className="space-y-8">
      {/* Header com Ação */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-3xl font-serif text-slate-900 tracking-tight">Estoque de Produtos</h1>
          <p className="text-slate-500 text-sm mt-1">Gerencie seu catálogo de roupas finas e controle de estoque.</p>
        </div>
        
        {podeEditar && (
          <motion.button
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            onClick={abrirNovoModal}
            className="flex items-center justify-center gap-2 bg-ebony hover:bg-reseda text-white font-medium px-5 py-3 rounded-xl transition duration-200 shadow-sm shadow-ebony/10 cursor-pointer text-sm"
          >
            <PlusCircle size={18} />
            <span>Novo Produto</span>
          </motion.button>
        )}
      </div>

      {/* Filtros e Barra de Busca */}
      <div className="flex flex-col md:flex-row gap-4 items-center justify-between bg-white p-4 rounded-xl border border-slate-100 shadow-sm">
        <div className="relative w-full md:max-w-md">
          <Search className="absolute left-3 top-3.5 text-slate-400" size={18} />
          <input
            type="text"
            placeholder="Buscar peça, linha ou categoria..."
            value={busca}
            onChange={(e) => setBusca(e.target.value)}
            className="w-full bg-slate-50 border border-slate-100 rounded-lg pl-10 pr-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-ebony focus:bg-white transition duration-200 text-slate-800"
          />
        </div>

        {/* Categoria Tags */}
        <div className="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto py-1 scrollbar-none">
          {categorias.map(cat => (
            <button
              key={cat}
              onClick={() => setCategoriaAtiva(cat)}
              className={`px-3.5 py-1.5 rounded-full text-xs font-semibold whitespace-nowrap transition duration-200 cursor-pointer ${
                categoriaAtiva === cat
                  ? 'bg-reseda text-white'
                  : 'bg-stone-50 text-stone-600 hover:bg-stone-100'
              }`}
            >
              {cat}
            </button>
          ))}
        </div>

        {/* Toggle Grade / Tabela */}
        <div className="flex items-center gap-1 bg-slate-100 p-1 rounded-lg">
          <button
            onClick={() => setModVisualizacao('grade')}
            className={`p-1.5 rounded-md cursor-pointer transition ${modVisualizacao === 'grade' ? 'bg-white text-reseda shadow-xs' : 'text-slate-400 hover:text-slate-600'}`}
          >
            <Grid size={16} />
          </button>
          <button
            onClick={() => setModVisualizacao('tabela')}
            className={`p-1.5 rounded-md cursor-pointer transition ${modVisualizacao === 'tabela' ? 'bg-white text-reseda shadow-xs' : 'text-slate-400 hover:text-slate-600'}`}
          >
            <List size={16} />
          </button>
        </div>
      </div>

      {produtosFiltrados.length === 0 ? (
        <div className="bg-white rounded-xl border border-slate-100 text-center py-16 px-4">
          <AlertCircle className="mx-auto text-stone-300 mb-4" size={48} />
          <h3 className="text-lg font-medium text-slate-700">Nenhum produto encontrado</h3>
          <p className="text-slate-400 text-sm max-w-md mx-auto mt-1">Não existem peças correspondentes ao filtro atual. Tente alterar a busca ou adicione novos produtos.</p>
        </div>
      ) : modVisualizacao === 'grade' ? (
        /* Visualização de Grade com fotos lindas das roupas */
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
          {produtosFiltrados.map((prod) => (
            <motion.div
              layout
              key={prod.id}
              initial={{ opacity: 0, scale: 0.95 }}
              animate={{ opacity: 1, scale: 1 }}
              exit={{ opacity: 0, scale: 0.95 }}
              className="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-xs hover:shadow-md transition-all duration-300 group flex flex-col justify-between"
            >
              {/* Contenedor da imagem */}
              <div className="relative h-64 bg-slate-100 overflow-hidden">
                <img
                  src={prod.imagemUrl}
                  alt={prod.nome}
                  referrerPolicy="no-referrer"
                  className="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
                
                {/* Categoria Badge */}
                <div className="absolute top-3 left-3 bg-stone-900/80 backdrop-blur-xs text-white text-[10px] font-bold px-2 rounded-md tracking-wider uppercase">
                  {prod.categoria || "Moda"}
                </div>

                {/* Tamanho Badge */}
                <div className="absolute top-3 right-3 bg-white text-slate-800 text-[10px] font-bold px-2 py-0.5 rounded-md shadow-xs">
                  TAM: {prod.tamanho || "M"}
                </div>

                {/* Aviso Estoque Baixo */}
                {prod.estoque < 10 && (
                  <div className="absolute bottom-3 left-3 flex items-center gap-1 bg-[#a64b2a]/95 text-white text-[10px] font-bold px-2.5 py-0.5 rounded-md shadow-sm">
                    <AlertTriangle size={10} />
                    <span>Estoque Crítico: {prod.estoque} un</span>
                  </div>
                )}
              </div>

              {/* Informações */}
              <div className="p-4 flex-1 flex flex-col justify-between space-y-3">
                <div>
                  <h4 className="text-sm font-medium text-slate-900 group-hover:text-reseda transition-colors line-clamp-1">
                    {prod.nome}
                  </h4>
                  <div className="flex items-baseline gap-2 mt-1">
                    <span className="text-lg font-bold text-slate-800">
                      R$ {prod.preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </span>
                  </div>
                </div>

                <div className="flex items-center justify-between pt-3 border-t border-slate-50 text-xs">
                  <span className={`font-semibold ${prod.estoque >= 10 ? 'text-slate-500' : 'text-[#a64b2a]'}`}>
                    Estoque: {prod.estoque} un
                  </span>

                  {podeEditar && (
                    <div className="flex items-center gap-1">
                      <button
                        onClick={() => abrirEditarModal(prod)}
                        className="p-1.5 bg-slate-50 text-slate-500 hover:text-reseda hover:bg-slate-100 rounded-md transition"
                        title="Editar peça"
                      >
                        <Edit3 size={14} />
                      </button>
                      <button
                        onClick={() => confirmarExclusao(prod.id, prod.nome)}
                        className="p-1.5 bg-slate-50 text-slate-500 hover:text-red-600 hover:bg-rose-50 rounded-md transition cursor-pointer"
                        title="Excluir peça"
                      >
                        <Trash2 size={14} />
                      </button>
                    </div>
                  )}
                </div>
              </div>
            </motion.div>
          ))}
        </div>
      ) : (
        /* Visualização de Tabela com Fotos em Miniatura */
        <div className="bg-white rounded-xl border border-slate-100 overflow-hidden shadow-sm">
          <div className="overflow-x-auto">
            <table className="w-full text-left border-collapse text-slate-500">
              <thead>
                <tr className="bg-slate-50 text-slate-400 font-medium text-[11px] tracking-wider uppercase border-b border-rose-50">
                  <th className="px-6 py-4">Peça</th>
                  <th className="px-6 py-4">Categoria</th>
                  <th className="px-6 py-4">Tamanho</th>
                  <th className="px-6 py-4 text-right">Preço Unitário</th>
                  <th className="px-6 py-4 text-center">Unidades</th>
                  <th className="px-6 py-4">Status</th>
                  {podeEditar && <th className="px-6 py-4 text-right">Ações</th>}
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 text-sm text-slate-700">
                {produtosFiltrados.map(prod => (
                  <tr key={prod.id} className="hover:bg-slate-50/50 transition">
                    <td className="px-6 py-3.5 font-medium text-slate-800">
                      <div className="flex items-center gap-4">
                        <img
                          src={prod.imagemUrl}
                          alt={prod.nome}
                          referrerPolicy="no-referrer"
                          className="w-12 h-12 object-cover rounded-md border border-slate-100"
                        />
                        <span className="font-semibold">{prod.nome}</span>
                      </div>
                    </td>
                    <td className="px-6 py-3.5">
                      <span className="px-2.5 py-1 bg-stone-100 text-stone-600 rounded-md text-xs font-medium">
                        {prod.categoria || "Moda"}
                      </span>
                    </td>
                    <td className="px-6 py-3.5 font-mono text-xs">{prod.tamanho || "M"}</td>
                    <td className="px-6 py-3.5 text-right font-bold">
                      R$ {prod.preco.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                    </td>
                    <td className="px-6 py-3.5 text-center font-semibold font-mono">{prod.estoque}</td>
                    <td className="px-6 py-3.5">
                      {prod.estoque >= 10 ? (
                        <span className="inline-flex items-center gap-1 text-emerald-600 font-semibold text-xs">
                          <span className="w-1.5 h-1.5 rounded-full bg-emerald-500" />
                          Regular
                        </span>
                      ) : (
                        <span className="inline-flex items-center gap-1 text-[#a64b2a] font-semibold text-xs">
                          <AlertTriangle size={12} />
                          Alerta Baixo
                        </span>
                      )}
                    </td>
                    {podeEditar && (
                      <td className="px-6 py-3.5 text-right">
                        <div className="inline-flex gap-1.5">
                          <button
                            onClick={() => abrirEditarModal(prod)}
                            className="p-1.5 text-slate-500 hover:text-reseda hover:bg-slate-100 rounded-md transition"
                          >
                            <Edit3 size={15} />
                          </button>
                          <button
                            onClick={() => confirmarExclusao(prod.id, prod.nome)}
                            className="p-1.5 text-slate-500 hover:text-red-700 hover:bg-rose-50 rounded-md transition"
                          >
                            <Trash2 size={15} />
                          </button>
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

      {/* MODAL PARA ADICIONAR / EDITAR PRODUTO */}
      <AnimatePresence>
        {mostrarModal && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Backdrop */}
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setMostrarModal(false)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />

            {/* Modal Box */}
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-lg bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-6"
            >
              <div>
                <h3 className="text-xl font-serif text-slate-900">
                  {editandoId !== null ? "Editar Peça do Estoque" : "Nova Peça para Cadastrar"}
                </h3>
                <p className="text-slate-400 text-xs">Adicione roupas finas e configure preços, quantidades e tamanhos.</p>
              </div>

              <form onSubmit={lidarComSalvar} className="space-y-4">
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Nome do Produto *</label>
                  <input
                    type="text"
                    required
                    value={formNome}
                    onChange={(e) => setFormNome(e.target.value)}
                    placeholder="Camisa Social Slim Preta"
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-sm"
                  />
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Preço (R$) *</label>
                    <input
                      type="text"
                      required
                      value={formPreco}
                      onChange={(e) => setFormPreco(e.target.value)}
                      placeholder="99,90"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-sm"
                    />
                  </div>
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Qtd em Estoque *</label>
                    <input
                      type="number"
                      required
                      min="0"
                      value={formEstoque}
                      onChange={(e) => setFormEstoque(e.target.value)}
                      placeholder="15"
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-sm"
                    />
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Categoria</label>
                    <select
                      value={formCategoria}
                      onChange={(e) => setFormCategoria(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-sm"
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
                    <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Tamanho</label>
                    <select
                      value={formTamanho}
                      onChange={(e) => setFormTamanho(e.target.value)}
                      className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2.5 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-sm"
                    >
                      <option value="P">P - Pequeno</option>
                      <option value="M">M - Médio</option>
                      <option value="G">G - Grande</option>
                      <option value="GG">GG - Extra Grande</option>
                      <option value="38">38</option>
                      <option value="40">40</option>
                      <option value="42">42</option>
                      <option value="Único">Tamanho Único</option>
                    </select>
                  </div>
                </div>

                {/* Selecionador de Imagem */}
                <div>
                  <label className="block text-xs font-bold text-slate-500 uppercase mb-1">Foto da Roupa (Catálogo)</label>
                  <p className="text-[10px] text-slate-400 mb-2">Selecione uma imagem de alta qualidade do nosso banco ou digite uma url personalizada:</p>
                  
                  {/* Presets Visual */}
                  <div className="grid grid-cols-4 sm:grid-cols-7 gap-2 mb-3">
                    {TEMPLATE_IMAGENS.map((preset, pIdx) => (
                      <button
                        key={pIdx}
                        type="button"
                        onClick={() => setFormImagemUrl(preset.url)}
                        className={`relative h-11 rounded-lg border-2 overflow-hidden transition ${
                          formImagemUrl === preset.url ? 'border-ebony scale-95 shadow-sm' : 'border-slate-100 hover:scale-105'
                        }`}
                        title={preset.nome}
                      >
                        <img src={preset.url} alt={preset.nome} referrerPolicy="no-referrer" className="w-full h-full object-cover" />
                      </button>
                    ))}
                  </div>

                  <input
                    type="url"
                    value={formImagemUrl}
                    onChange={(e) => setFormImagemUrl(e.target.value)}
                    placeholder="Cole o link HTTPS de uma imagem..."
                    className="w-full bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-1 focus:ring-ebony text-xs"
                  />
                </div>

                {/* Submit */}
                <div className="flex items-center justify-end gap-3 pt-4 border-t border-slate-50 text-sm">
                  <button
                    type="button"
                    onClick={() => setMostrarModal(false)}
                    className="px-4 py-2 bg-stone-50 text-stone-600 hover:bg-stone-100 rounded-lg transition"
                  >
                    Cancelar
                  </button>
                  <button
                    type="submit"
                    className="px-5 py-2.5 bg-ebony hover:bg-reseda text-white rounded-lg font-medium transition"
                  >
                    Salvar Produto
                  </button>
                </div>
              </form>
            </motion.div>
          </div>
        )}
      </AnimatePresence>

      {/* Toast Notificação de Sucesso ou Erro */}
      <AnimatePresence>
        {notificacao && (
          <motion.div
            initial={{ opacity: 0, y: -50, scale: 0.9 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: -50, scale: 0.9 }}
            className={`fixed top-6 right-6 z-55 p-4 rounded-xl shadow-xl flex items-center gap-3 border ${
              notificacao.tipo === 'erro' 
                ? 'bg-rose-950 border-rose-900/40 text-rose-200' 
                : 'bg-stone-900 border-stone-800 text-white'
            }`}
          >
            {notificacao.tipo === 'erro' ? (
              <AlertTriangle className="text-rose-400 shrink-0" size={22} />
            ) : (
              <PlusCircle className="text-sage shrink-0" size={22} />
            )}
            <div className="text-left text-xs">
              <p className={`font-bold uppercase tracking-wider ${notificacao.tipo === 'erro' ? 'text-rose-400' : 'text-sage'}`}>
                {notificacao.tipo === 'erro' ? 'Atenção' : 'Operação Efetuada'}
              </p>
              <p className="text-stone-300 font-medium">{notificacao.texto}</p>
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* CONFIRMAÇÃO EXCLUIR PRODUTO */}
      <AnimatePresence>
        {produtoExclusao && (
          <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <motion.div
              initial={{ opacity: 0 }}
              animate={{ opacity: 1 }}
              exit={{ opacity: 0 }}
              onClick={() => setProdutoExclusao(null)}
              className="absolute inset-0 bg-slate-900/60 backdrop-blur-xs"
            />
            <motion.div
              initial={{ opacity: 0, scale: 0.95, y: 15 }}
              animate={{ opacity: 1, scale: 1, y: 0 }}
              exit={{ opacity: 0, scale: 0.95, y: 15 }}
              className="relative w-full max-w-sm bg-white rounded-2xl shadow-xl overflow-hidden z-10 p-6 space-y-5 text-slate-800 text-left"
            >
              <div className="text-center space-y-2">
                <div className="mx-auto w-12 h-12 rounded-full bg-rose-50 flex items-center justify-center">
                  <AlertTriangle className="text-red-600 animate-bounce" size={22} />
                </div>
                <h3 className="text-lg font-serif text-slate-950 font-semibold">Excluir Produto</h3>
                <p className="text-slate-500 text-xs leading-relaxed">
                  Deseja realmente excluir permanentemente a peça <strong className="text-slate-800">"{produtoExclusao.nome}"</strong> do catálogo do estoque? Esta ação é irreversível.
                </p>
              </div>

              <div className="flex items-center justify-center gap-2.5 pt-2">
                <button
                  type="button"
                  onClick={() => setProdutoExclusao(null)}
                  className="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl transition text-xs font-semibold cursor-pointer text-center"
                >
                  Retroceder
                </button>
                <button
                  type="button"
                  onClick={lidarComConfirmarExclusao}
                  className="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-xl font-bold transition text-xs uppercase tracking-wider cursor-pointer text-center"
                >
                  Sim, Excluir
                </button>
              </div>
            </motion.div>
          </div>
        )}
      </AnimatePresence>
    </div>
  );
}
