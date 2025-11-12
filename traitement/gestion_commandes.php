
                <?php
                include('../connexion/connexion.php');

                if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'start_order') {
                    $client_name = $_POST['client_name'];
                    $date = date('Y-m-d H:i:s');

                    if (empty($client_name)) {
                        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Le nom du client est requis pour démarrer la commande.'];
                        header('Location: ../views/ventes.php');
                        exit();
                    }

                    try {
                        // Insérer la nouvelle commande dans la table 'commande'
                        $sql = "INSERT INTO `commande` (`date`, `nom_client`, `etat`) VALUES (:date, :client, 'En cours')";
                        $req = $connexion->prepare($sql);
                        $req->bindValue(':date', $date);
                        $req->bindValue(':client', $client_name);
                        $req->execute();

                        // Stocker l'ID de la nouvelle commande dans la session
                        $new_command_id = $connexion->lastInsertId();
                        $_SESSION['current_commande_id'] = $new_command_id;

                        $_SESSION['message'] = [
                            'type' => 'success',
                            'text' => 'Nouvelle commande démarrée pour ' . htmlspecialchars($client_name) . '. Ajoutez des produits.'
                        ];
                    } catch (PDOException $e) {
                        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erreur lors du démarrage de la commande : ' . $e->getMessage()];
                    }
                }
                // Finalisation de la commande (bouton Finaliser)
                else if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'finalize_order') {
                    if (isset($_SESSION['current_commande_id'])) {
                        $commande_id = $_SESSION['current_commande_id'];

                        try {
                            // Optionnel: Mettre l'état de la commande à 'Payée'
                            $sql = "UPDATE `commande` SET `etat` = 'Payée' WHERE `id` = :id";
                            $req = $connexion->prepare($sql);
                            $req->bindValue(':id', $commande_id);
                            $req->execute();

                            // Vider la session pour permettre une nouvelle commande
                            unset($_SESSION['current_commande_id']);

                            $_SESSION['message'] = [
                                'type' => 'success',
                                'text' => 'La commande #' . $commande_id . ' a été finalisée avec succès et payée.'
                            ];
                            // Optionnel : rediriger vers la facture (facture.php?id_commande=...)

                        } catch (PDOException $e) {
                            $_SESSION['message'] = ['type' => 'danger', 'text' => 'Erreur lors de la finalisation de la commande : ' . $e->getMessage()];
                        }
                    } else {
                        $_SESSION['message'] = ['type' => 'danger', 'text' => 'Aucune commande en cours à finaliser.'];
                    }
                }

                $connexion = null;
                header('Location: ../views/ventes.php');
                exit();
                ?>