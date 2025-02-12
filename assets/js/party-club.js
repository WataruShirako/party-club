document.addEventListener("DOMContentLoaded", () => {
  // クラス名 "party-club-participate-button" を持つすべてのボタンを取得
  const buttons = document.querySelectorAll(".party-club-participate-button");
  for (const button of buttons) {
    button.addEventListener("click", () => {
      // クリックされたボタンの最も近いコンテナ (.party-club-participation) を取得
      const container = button.closest(".party-club-participation");
      if (!container) {
        console.error("(.party-club-participation) が見つかりません。");
        return;
      }
      // コンテナの data-event-id 属性からイベントIDを取得
      const eventId = container.getAttribute("data-event-id");

      // ボタンを無効化し、処理中のメッセージを表示
      button.disabled = true;
      const messageEl = container.querySelector(
        ".party-club-participation-message",
      );
      // if (messageEl) {
      //   messageEl.textContent = "処理中...";
      // }

      // トグル用の REST API エンドポイントにリクエスト
      fetch(`${partyClubSettings.root}party-club/v1/toggle-registration`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": partyClubSettings.nonce,
        },
        body: JSON.stringify({ event_id: eventId }),
      })
        .then((response) => {
          if (!response.ok) {
            return response.json().then((data) => {
              throw new Error(data.message || "もう一度お試しください");
            });
          }
          return response.json();
        })
        .then((data) => {
          // 結果に応じてボタンの文言を切り替え
          if (data.registered) {
            button.textContent = "登録済み";
            button.classList.add("is_registered");
          } else if (data.unregistered) {
            button.textContent = "参加する";
            button.classList.remove("is_registered");
          }
          if (messageEl) {
            messageEl.textContent = "";
          }
          button.disabled = false;
          location.reload();
        })
        .catch((error) => {
          button.disabled = false;
          if (messageEl) {
            messageEl.textContent = error.message;
          }
        });
    });
  }
});
