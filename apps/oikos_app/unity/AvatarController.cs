using UnityEngine;
using System.Runtime.InteropServices;

public class AvatarController : MonoBehaviour
{
    private Animator animator;

    [Header("Identidade do Membro")]
    [SerializeField]
    private string memberId = "m_default";

    // Importa o método JavaScript definido no plugin .jslib do Unity WebGL
    #if !UNITY_EDITOR && UNITY_WEBGL
    [DllImport("__Internal")]
    private static extern void SendMemberSelected(string id);
    #endif

    void Start()
    {
        animator = GetComponent<Animator>();
        PlayIdle();
    }

    public void PlayIdle()
    {
        if (animator != null)
        {
            animator.SetTrigger("Idle");
        }
    }

    public void PlayJump()
    {
        if (animator != null)
        {
            animator.SetTrigger("Jump");
        }
    }

    public void PlayWave()
    {
        if (animator != null)
        {
            animator.SetTrigger("Wave");
        }
    }

    // Configura o ID do membro reativamente
    public void SetMemberId(string id)
    {
        this.memberId = id;
    }

    // Detecta o clique físico no objeto/avatar 3D no Unity
    void OnMouseDown()
    {
        PlayJump();

        // Envia o ID do membro selecionado para a página web pai (Flutter Web)
        #if !UNITY_EDITOR && UNITY_WEBGL
        try
        {
            SendMemberSelected(memberId);
        }
        catch (System.Exception e)
        {
            Debug.LogError("Erro ao comunicar com Flutter Web: " + e.Message);
        }
        #else
        Debug.Log("Avatar 3D clicado no Editor. MemberId: " + memberId);
        #endif
    }
}
