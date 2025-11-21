import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { isAllowedFileType, formatFileSize } from '../utils/fileTypes';

/**
 * Composable para gerenciar documentos
 * Single Responsibility: Gerenciar upload e lista de documentos
 */
export function useDocuments(initialDocuments = []) {
  const documents = ref(initialDocuments);
  const uploading = ref(false);
  const uploadProgress = ref(0);
  const uploadError = ref(null);

  /**
   * Adiciona um documento à lista
   */
  function addDocument(file) {
    if (!isAllowedFileType(file.name)) {
      uploadError.value = 'Tipo de arquivo não permitido. Use PDF, KML ou DOCX.';
      return false;
    }

    if (file.size > 50 * 1024 * 1024) { // 50MB
      uploadError.value = 'Arquivo muito grande. Máximo: 50MB.';
      return false;
    }

    const newDoc = {
      id: Date.now(),
      nome: file.name,
      tamanho: formatFileSize(file.size),
      arquivo: file,
      status: 'pending',
    };

    documents.value.push(newDoc);
    return true;
  }

  /**
   * Remove um documento da lista
   */
  function removeDocument(id) {
    documents.value = documents.value.filter(doc => doc.id !== id);
  }

  /**
   * Faz upload dos documentos
   */
  function uploadDocuments(empreendimentoId) {
    if (documents.value.length === 0) {
      return;
    }

    uploading.value = true;
    uploadError.value = null;
    uploadProgress.value = 0;

    const formData = new FormData();
    documents.value.forEach((doc, index) => {
      if (doc.arquivo) {
        formData.append(`documents[${index}]`, doc.arquivo);
      }
    });

    router.post(`/pae/${empreendimentoId}/documents`, formData, {
      forceFormData: true,
      onProgress: (progress) => {
        uploadProgress.value = progress.percentage;
      },
      onSuccess: () => {
        uploading.value = false;
        uploadProgress.value = 100;
        // Atualizar lista de documentos
      },
      onError: (errors) => {
        uploading.value = false;
        uploadError.value = errors.message || 'Erro ao fazer upload dos documentos';
      },
    });
  }

  /**
   * Limpa erros de upload
   */
  function clearError() {
    uploadError.value = null;
  }

  return {
    documents,
    uploading,
    uploadProgress,
    uploadError,
    addDocument,
    removeDocument,
    uploadDocuments,
    clearError,
  };
}

